import { Calendar as FullCalendar } from "@fullcalendar/core"
import interactionPlugin from "@fullcalendar/interaction"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import listPlugin from "@fullcalendar/list"
import { DateTime } from "luxon"
import BulkEdit from "./bulkEdit"
import PopOver from "./popover"
import ContextMenu from "./contextMenu"
import UserConfig from "./userConfig"
import SearchBar from "./searchBar"
import API from "./api"
import Page from "./page"

class Calendar {

    static _fc = null
    static _showAvailability = true

    // Great contrast colors from ...
    // https://sashamaps.net/docs/resources/20-colors/
    static accessableColors = [
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
        //"#000075", // Navy
        "#a9a9a9", // Grey
    ].map(value => ({ value, sort: Math.random() }))
        .sort((a, b) => a.sort - b.sort)
        .map(({ value }) => value)

    static toolbars = {
        edit: {
            topRight: ["search", "singleMonth,singleWeek,singleDay"],
            topLeft: ["prev,next", "today", "config", "bulk"],
            bottomRight: []
        },
        schedule: {
            topRight: ["search", "agenda,singleMonth,singleWeek,singleDay"],
            topLeft: ["prev,next", "today", "config", "availability"],
            bottomRight: []
        },
        my: {
            topRight: ["search", "agenda,singleMonth,singleWeek,singleDay"],
            topLeft: ["prev,next", "today", "config"],
            bottomRight: []
        }
    }

    static refresh = () => { Calendar._fc.refetchEvents() }
    static render = () => { Calendar._fc.render() }
    static getView = () => { return Calendar._fc.view.type }
    static getEvent = (id) => { return Calendar._fc.getEventById(id) }

    static showLoading() {
        document.getElementById("loader").classList.remove("d-none")
    }

    static hideLoading() {
        document.getElementById("loader").classList.add("d-none")
    }

    static isLoadingVisible() {
        return !document.getElementById("loader").classList.contains("d-none")
    }

    static init() {

        // Modify toolbars
        Calendar.toolbars = Calendar.toolbars[Page.type]
        Calendar.toolbars.bottomRight = Page.refer ? ["refer"] : Calendar.toolbars.bottomRight

        // Grab user settings
        const { start: startTime, end: endTime, hiddenDays, slotSize, expandRows, limitAvailability } = UserConfig.get()

        Calendar._fc = new FullCalendar(document.getElementById("calendar"), {
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
                    click: () => location.href = Page.refer
                },
                bulk: {
                    text: "Bulk Edit",
                    click: BulkEdit.open
                },
                availability: {
                    icon: "fa-eye",
                    click: () => {
                        const o = Calendar._showAvailability ? ["fa-eye", "fa-eye-slash"] : ["fa-eye-slash", "fa-eye"]
                        Calendar._showAvailability = !Calendar._showAvailability
                        document.querySelector(".fc-availability-button ." + o[0]).classList.replace(o[0], o[1])
                        Calendar.refresh()
                    }
                }
            },
            headerToolbar: {
                left: Calendar.toolbars.topLeft.join(" "),
                center: "title",
                right: Calendar.toolbars.topRight.join(" ")
            },
            footerToolbar: {
                right: Calendar.toolbars.bottomRight.join(" ")
            },
            slotDuration: `00:${slotSize}:00`,
            navLinks: true,
            editable: false,
            dayMaxEvents: true,
            initialView: "singleWeek",
            slotMinTime: startTime,
            slotMaxTime: endTime,
            expandRows: expandRows,
            selectable: Page.type != "my",
            dateClick: (dateClickInfo) => {
                if (dateClickInfo.view.type == "singleMonth") {
                    Calendar._fc.changeView("singleWeek")
                    Calendar._fc.gotoDate(dateClickInfo.date)
                }
            },
            select: (selectionInfo) => {
                if (["singleWeek", "singleDay"].includes(Calendar.getView())) {
                    if (Page.type == "edit") {
                        PopOver.openAvailability(selectionInfo)
                    } else if (Page.type == "schedule") {
                        PopOver.openScheduleVisit(selectionInfo)
                    }
                }
            },
            selectAllow: (selectionInfo) => {
                // Prevent from selecting multiple days
                if (["singleMonth", "singleWeek"].includes(Calendar.getView())) {
                    const start = DateTime.fromISO(selectionInfo.endStr)
                    const end = DateTime.fromISO(selectionInfo.startStr)
                    return start.diff(end, "days").toObject().days <= 1
                }
                return true
            },
            eventDidMount: (arg) => {
                const props = arg.event.extendedProps
                arg.el.setAttribute('data-internal-id', props.internal_id)
                if (["singleDay", "singleWeek"].includes(Calendar.getView())) {
                    if (Page.type == "edit") {
                        ContextMenu.attachContextMenu(arg.el, ContextMenu.availabilityMenu)
                    } else if (Page.type == "schedule" && props.is_appointment) {
                        ContextMenu.attachContextMenu(arg.el, ContextMenu.appointmentMenu)
                    }
                }
            },
            unselect: function (jsEvent, view) {
                // TODO close any open forms
            },
            loading: (isLoading) => {
                Calendar[isLoading ? "showLoading" : "hideLoading"]()
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
                            `${info.timeText}<br>${props.record_display}<br>${props.visit_display}<br>${props.location_display}`,
                        "schedule":
                            `${info.timeText}<br>${props.record_display}<br>${props.visit_display}<br>${props.user_display}<br>${props.location_display}`,
                    },
                }[type][Page.type]

                return { html: title }
            },
            events: (info, successCallback, failureCallback) => {
                let paramsCommon = {
                    start: info.start.toISOString(),
                    end: info.end.toISOString(),
                    providers: SearchBar.getPickedProviders(true),
                    locations: SearchBar.getPickedLocations(true),
                }

                let paramsAvailability = {
                    all_availability: !limitAvailability,
                }

                let paramsAppointment = {
                    subjects: SearchBar.getPickedSubjects(true),
                    visits: SearchBar.getPickedEvents(true),
                }

                let colors = {}
                const commonProcessing = (calEvent) => {
                    // Copy all non-standard fields to extendedProps
                    // Assign unique colors to each provider
                    calEvent["id"] = calEvent["internal_id"]
                    for (const [key, value] of Object.entries(calEvent)) {
                        if (!["id", "start", "end", "title"].includes(key)) {
                            calEvent.extendedProps = calEvent.extendedProps || {}
                            calEvent.extendedProps[key] = value
                        }
                    }
                    const color = colors[calEvent.user] || Calendar.accessableColors[Object.keys(colors).length % Calendar.accessableColors.length]
                    calEvent.color = color
                    colors[calEvent.user] = color
                    return calEvent
                }

                let availabilityPromise = ["schedule", "edit"].includes(Page.type) && Calendar._showAvailability ? API.getAvailability({ ...paramsCommon, ...paramsAvailability }) : Promise.resolve([])
                let appointmentPromise = ["schedule", "my"].includes(Page.type) ? API.getAppointments({ ...paramsCommon, ...paramsAppointment }) : Promise.resolve([])

                Promise.all([availabilityPromise, appointmentPromise]).then(([availabilityData, appointmentData]) => {
                    let data = availabilityData.concat(appointmentData)
                    data.forEach((event) => {
                        event = commonProcessing(event)

                        // If on schedule page, send the availability to background
                        if (Page.type == "schedule" && event.is_availability) {
                            event.display = "background"
                        }
                    })

                    successCallback(data)
                }).catch((error) => {
                    failureCallback(error)
                })
            }
        })
    }
}

export default Calendar