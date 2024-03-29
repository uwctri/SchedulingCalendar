import { Calendar } from "@fullcalendar/core"
import interactionPlugin from "@fullcalendar/interaction"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import listPlugin from "@fullcalendar/list"
import { DateTime } from "luxon"
import UserConfig from "./userConfig"
import BulkEdit from "./bulkEdit"
import SearchBar from "./searchBar"
import PopOver from "./popover"
import Loading from "./loading"
import ContextMenu from "./contextMenu"
import API from "./api"
import "./iconObserver"
import "./style.less"

// Great contrast colors from ...
// https://sashamaps.net/docs/resources/20-colors/
const accessableColors = [
    "#e6194B", // Red
    "#3cb44b", // Green
    //"#ffe119", // Yellow
    "#4363d8", // Blue
    "#f58231", // Orange
    "#42d4f4", // Cyan
    // "#f032e6", // Magenta
    "#fabed4", // Pink
    "#469990", // Teal
    "#dcbeff", // Lavender
    "#9A6324", // Brown
    "#fffac8", // Beige
    "#800000", // Maroon
    "#aaffc3", // Mint
    "#000075", // Navy
    "#a9a9a9", // Grey
].map(value => ({ value, sort: Math.random() }))
    .sort((a, b) => a.sort - b.sort)
    .map(({ value }) => value)

// Load user config and FC toolbar
const coreEventFields = ["start", "end", "title"];
const pageURL = Object.fromEntries(new URLSearchParams(location.search))
const { start: startTime, end: endTime, hiddenDays, slotSize, expandRows } = UserConfig.get()
let topRightToolbar = ["search", "singleMonth,singleWeek,singleDay"]
let topLeftToolbar = ["prev,next", "today", "config"]
let bottomRightToolbar = [];
if (!pageURL.type)
    pageURL.type = "edit"// TODO default to schedule
if (["schedule", "my"].includes(pageURL.type))
    topRightToolbar[1] = `agenda,${topRightToolbar[1]}`
if (pageURL.type == "edit")
    topLeftToolbar.push("bulk")
if (pageURL.refer)
    bottomRightToolbar = ["refer"]

document.getElementsByClassName(`type-${pageURL.type}`)[0].classList.add('active')

// Init the calendar
calendar = new Calendar(document.getElementById("calendar"), {
    plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin],
    customButtons: {
        config: {
            icon: "fa-gear",
            click: UserConfig.open
        },
        search: {
            icon: "fa-magnifying-glass",
            click: SearchBar.toggle
        },
        refer: {
            text: "Return to Workflow",
            click: () => location.href = pageURL.refer
        },
        bulk: {
            text: "Bulk Edit",
            click: BulkEdit.open
        }
    },
    headerToolbar: {
        left: topLeftToolbar.join(" "),
        center: "title",
        right: topRightToolbar.join(" ")
    },
    footerToolbar: {
        right: bottomRightToolbar.join(" ")
    },
    slotDuration: `00:${slotSize}:00`,
    navLinks: true,
    editable: false,
    dayMaxEvents: true,
    initialView: "singleWeek",
    slotMinTime: startTime,
    slotMaxTime: endTime,
    expandRows: expandRows,
    selectable: pageURL.type != "my",
    dateClick: (dateClickInfo) => {
        if (dateClickInfo.view.type == "singleMonth") {
            calendar.changeView("singleWeek")
            calendar.gotoDate(dateClickInfo.date)
        }
    },
    select: (selectionInfo) => {
        if (pageURL.type == "edit" && ["singleWeek", "singleDay"].includes(calendar.view.type)) {
            PopOver.openAvailability(selectionInfo)
        }
    },
    selectAllow: (selectionInfo) => {
        // Prevent from selecting multiple days
        if (["singleMonth", "singleWeek"].includes(calendar.view.type)) {
            const start = DateTime.fromISO(selectionInfo.endStr)
            const end = DateTime.fromISO(selectionInfo.startStr)
            return start.diff(end, "days").toObject().days <= 1
        }
        return true
    },
    eventDidMount: (arg) => {
        arg.el.setAttribute('data-internal-id', arg.event.extendedProps.internal_id)
        if (["singleMonth", "singleWeek"].includes(calendar.view.type) && pageURL.type == "edit") {
            ContextMenu.attachContextMenu(arg.el, ContextMenu.availabilityMenu)
        }
    },
    unselect: function (jsEvent, view) {
        // TODO close any open forms
    },
    loading: (isLoading) => {
        Loading[isLoading ? "show" : "hide"]()
    },
    unselectAuto: false,
    views: {
        singleMonth: {
            type: "dayGridMonth",
        },
        singleDay: {
            allDaySlot: false,
            type: "timeGridDay",
        },
        singleWeek: {
            type: "timeGridWeek",
            hiddenDays: hiddenDays,
            buttonText: "week",
            allDaySlot: false
        },
        agenda: {
            type: "list",
            visibleRange: () => {
                // half year forward and back
                let dt = DateTime.now()
                return {
                    start: dt.minus({ day: 364 / 2 }).toJSDate(),
                    end: dt.plus({ day: 364 / 2 }).toJSDate()
                }
            },
            listDayFormat: {
                month: "long",
                year: "numeric",
                day: "numeric",
                weekday: "long"
            },
            buttonText: "agenda"
        }
    },
    eventContent: (info) => {
        const props = info.event.extendedProps
        const type = props.is_availability ? "availability" : "appointment"
        let title = {
            "availability": {
                "edit":
                    `${info.timeText}<br>${props.availability_code_display}<br>${props.user_display}<br>${props.location_display}`,
                "schedule":
                    `${props.user_display}<br>${props.location_display}`,
            },
            "appointment": {
                "my":
                    `${info.timeText}<br>Subject's Display Name<br>Event Display Name<br>${props.location_display}`, // TODO
                "schedule":
                    `${info.timeText}<br>Subject's Display Name<br>Event Display Name<br>${props.location_display}`, // TODO
            },
        }[type][pageURL.type]

        return { html: title };
    },
    events: (info, successCallback, failureCallback) => {
        let paramsCommon = {
            start: info.start.toISOString(),
            end: info.end.toISOString(),
            page: pageURL.type,
            providers: SearchBar.getPickedProviders(true),
            locations: SearchBar.getPickedLocations(true),
        }

        let paramsAppointment = {
            subjects: SearchBar.getPickedSubjects(true),
            visits: SearchBar.getPickedEvents(true),
        }

        let colors = {}
        const commonProcessing = (calEvent) => {
            // Copy all non-standard fields to extendedProps
            // Assign unique colors to each provider
            for (const [key, value] of Object.entries(calEvent)) {
                if (!coreEventFields.includes(key)) {
                    calEvent.extendedProps = calEvent.extendedProps || {}
                    calEvent.extendedProps[key] = value
                }
            }
            const color = colors[calEvent.user] || accessableColors[Object.keys(colors).length % accessableColors.length]
            calEvent.color = color
            colors[calEvent.user] = color
            return calEvent
        }

        if (["schedule", "edit"].includes(pageURL.type)) {
            API.getAvailability(paramsCommon).then((data) => {
                data.forEach((event) => {
                    event = commonProcessing(event)

                    // If on schedule page, send the availability to background
                    if (pageURL.type == "schedule") {
                        event.display = "background"
                    }
                })
                successCallback(data)
            }).catch((error) => {
                failureCallback(error)
            })
        }

        // TODO the below doesn't work yet
        if (["schedule", "my"].includes(pageURL.type)) {
            API.getAppointments(...paramsCommon, ...paramsAppointment).then((data) => {
                data.forEach((event) => {
                    event = commonProcessing(event)
                })
                successCallback(data)
            }).catch((error) => {
                failureCallback(error)
            })
        }
    }
})

document.getElementById("content").classList.remove("d-none")
document.getElementById("pageMenu").classList.remove("d-none")
calendar.render()
SearchBar.build()
setInterval(() => calendar.refetchEvents(), 1000 * 60 * 2)
