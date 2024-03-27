import { Calendar } from "@fullcalendar/core"
import interactionPlugin from "@fullcalendar/interaction"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import listPlugin from "@fullcalendar/list"
import { DateTime } from "luxon"
import UserConfig from "./userConfig"
import SearchBar from "./searchBar"
import PopOver from "./popover"
import Loading from "./loading"
import ContextMenu from "./contextmenu"
import API from "./api"
import "./iconObserver"
import "./style.less"

// Great contrast colors from ...
// https://sashamaps.net/docs/resources/20-colors/
const accessableColors = [
    "#e6194B", // Red
    "#3cb44b", // Green
    "#ffe119", // Yellow
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
const topLeftToolbar = ["prev,next", "today", "config"]
let bottomRightToolbar = [];
if (!pageURL.type) {
    location.href = `${location.href}&type=edit` // TODO default to schedule
}
if (pageURL.type != "edit") {
    topRightToolbar[1] = `agenda,${topRightToolbar[1]}`
}
if (pageURL.refer) {
    bottomRightToolbar = ["refer"]
}
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
            text: "Return to workflow",
            click: () => location.href = pageURL.refer
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
        // TODO we need to determine if its an appointment or availability
        const props = info.event.extendedProps
        let title = {
            "edit":
                `${info.timeText}<br>${props.availability_code_display}<br>${props.user_display}<br>${props.location_display}`,
            "my":
                `${info.timeText}<br>${props.availability_code_display}<br>${props.location_display}`,
            "schedule":
                `${props.user_display}<br>${props.location_display}`,
        }[pageURL.type]

        return { html: title };
    },
    events: (info, successCallback, failureCallback) => {
        const paramsAvailability = {
            start: info.start.toISOString(),
            end: info.end.toISOString(),
            page: pageURL.type,
            providers: SearchBar.getPickedProviders(true),
            locations: SearchBar.getPickedLocations(true),
            subjects: [], // Not used in Availability
            events: [],// Not used in Availability
        }

        if (["schedule", "edit"].includes(pageURL.type)) {
            API.getAvailability(paramsAvailability).then((data) => {
                // Copy all non-standard fields to extendedProps
                // Assign unique colors to each provider
                let colors = {}
                data.forEach((event) => {
                    for (const [key, value] of Object.entries(event)) {
                        if (!coreEventFields.includes(key)) {
                            event.extendedProps = event.extendedProps || {}
                            event.extendedProps[key] = value
                        }
                    }
                    let color = colors[event.user] || accessableColors[Object.keys(colors).length]
                    event.color = color
                    colors[event.user] = color

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
    }
})

document.getElementById("content").classList.remove("d-none")
document.getElementById("pageMenu").classList.remove("d-none")
calendar.render()
SearchBar.build()
setInterval(() => calendar.refetchEvents(), 1000 * 60 * 2)
