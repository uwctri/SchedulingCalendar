import { Calendar as FullCalendar } from "@fullcalendar/core"
import interactionPlugin from "@fullcalendar/interaction"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import listPlugin from "@fullcalendar/list"
import { DateTime } from "luxon"
import BulkEdit from "./bulkEdit"
import PopOver from "./popover"
import { genRowCol } from "./utils"
import ContextMenu from "./contextMenu"
import UserConfig from "./userConfig"
import ICS from "./ics"
import CleanUp from "./cleanup"
import SearchBar from "./searchBar"
import API from "./api"
import Page from "./page"
import RedCap from "./redcap"

const autoRefreshTime = 120 // seconds
class Calendar {

    static _fc = null
    static _showAvailability = true
    static _refreshTime = DateTime.now().minus({ minutes: 60 })

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
            bottomRight: [],
            bottomLeft: [],
        },
        schedule: {
            topRight: ["search", "agenda,singleMonth,singleWeek,singleDay"],
            topLeft: ["prev,next", "today", "config", "availability"],
            bottomRight: [],
            bottomLeft: [],
        },
        my: {
            topRight: ["search", "agenda,singleMonth,singleWeek,singleDay"],
            topLeft: ["prev,next", "today", "config"],
            bottomRight: [],
            bottomLeft: [],
        }
    }

    static refresh = () => {
        Calendar._refreshTime = DateTime.now()
        Calendar._fc.refetchEvents()
    }

    static render = () => { Calendar._fc.render() }
    static getView = () => { return Calendar._fc.view.type }
    static getEvent = (id) => { return Calendar._fc.getEventById(id) }

    static showLoading() {
        $.getElementById("loader").classList.remove("d-none")
    }

    static hideLoading() {
        $.getElementById("loader").classList.add("d-none")
    }

    static isLoadingVisible() {
        return !$.getElementById("loader").classList.contains("d-none")
    }

    static init() {

        // Every 30 seconds check to see if a hard pull has occured in the past N minutes
        // and pull if not
        setInterval(() => {
            if (DateTime.now().diff(Calendar._refreshTime).seconds >= autoRefreshTime)
                Calendar.refresh()
        }, 1000 * 30)

        // Modify toolbars
        Calendar.toolbars = Calendar.toolbars[Page.type]
        Calendar.toolbars.bottomRight = Page.refer ? ["refer"] : Calendar.toolbars.bottomRight
        Calendar.toolbars.bottomLeft = RedCap.user.isCalendarAdmin ? ["cleanup", "ics"] : Calendar.toolbars.bottomLeft

        // Grab user settings
        const { start: startTime, end: endTime, hiddenDays, slotSize, expandRows, lineHeight, limitAvailability } = UserConfig.get()

        Calendar._fc = new FullCalendar($.getElementById("calendar"), {
            plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin],
            customButtons: {
                today: {
                    text: RedCap.tt("btn_today")
                },
                cleanup: {
                    icon: "fa-broom",
                    hint: RedCap.tt("alt_cleanup"),
                    click: CleanUp.open
                },
                ics: {
                    text: ".ICS",
                    hint: RedCap.tt("alt_ics"),
                    click: ICS.open
                },
                config: {
                    icon: "fa-gear",
                    hint: RedCap.tt("alt_user"),
                    click: UserConfig.open
                },
                search: {
                    icon: "fa-magnifying-glass",
                    hint: RedCap.tt("alt_search"),
                    click: SearchBar.toggle
                },
                refer: {
                    text: RedCap.tt("btn_return"),
                    click: () => location.href = Page.refer
                },
                bulk: {
                    text: RedCap.tt("btn_bulk"),
                    click: BulkEdit.open
                },
                availability: {
                    icon: "fa-eye",
                    hint: RedCap.tt("btn_tog"),
                    click: () => {
                        const o = Calendar._showAvailability ? ["fa-eye", "fa-eye-slash"] : ["fa-eye-slash", "fa-eye"]
                        Calendar._showAvailability = !Calendar._showAvailability
                        $.querySelector(".fc-availability-button ." + o[0]).classList.replace(o[0], o[1])
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
                left: Calendar.toolbars.bottomLeft.join(" "),
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
            eventClick: (eventClickInfo) => {
                const props = eventClickInfo.event.extendedProps
                if (["singleWeek", "singleDay"].includes(eventClickInfo.view.type) &&
                    ["my", "schedule"].includes(Page.type) &&
                    props.is_appointment) {
                    PopOver.openDetails(eventClickInfo)
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

                // Context Menus (Right clicks)
                if (["singleDay", "singleWeek", "agenda"].includes(Calendar.getView())) {
                    if (Page.type == "edit") {
                        ContextMenu.attachContextMenu(arg.el, ContextMenu.availabilityMenu)
                    } else if (Page.type == "schedule" && props.is_appointment) {
                        ContextMenu.attachContextMenu(arg.el, ContextMenu.appointmentMenu)
                    } else if (Page.type == "my") {
                        ContextMenu.attachContextMenu(arg.el, ContextMenu.readMenu)
                    }
                }

                // Adjust line height to user settings for non-background events
                if (["singleDay", "singleWeek"].includes(Calendar.getView()) && arg.event.display !== "background") {
                    arg.el.style.lineHeight = lineHeight

                    // Stop overflow on the scheduling cal, it's hard to read
                    if (Page.type == "schedule") {
                        arg.el.classList.add("overflow-hidden")
                    }
                }
            },
            loading: (isLoading) => {
                Calendar[isLoading ? "showLoading" : "hideLoading"]()
            },
            unselectAuto: false,
            views: {
                singleMonth: {
                    type: "dayGridMonth",
                    buttonText: RedCap.tt("btn_month"),
                },
                singleDay: {
                    allDaySlot: false,
                    type: "timeGridDay",
                    buttonText: RedCap.tt("btn_day"),
                },
                singleWeek: {
                    type: "timeGridWeek",
                    hiddenDays: hiddenDays,
                    buttonText: RedCap.tt("btn_week"),
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
                    buttonText: RedCap.tt("btn_agenda")
                }
            },
            eventContent: (info) => {
                const props = info.event.extendedProps
                const type = props.is_availability ? "availability" : "appointment"
                const view = Calendar.getView()
                let title = "Missing Title"


                if (view == "agenda") {
                    // Agenda view, only on Scheduling/My page, won't show anything but appts
                    title = `${props.record_display} - ${props.visit_display}<br>${props.user_display}<br>${props.location_display}`
                } else if (view == "singleMonth") {
                    // Month, appointments only
                    title = `${info.timeText} ${props.record_display}<br>${props.visit_display} | ${props.user_display}<br>${props.location_display}`
                } else {
                    // Week and Day format
                    title = {
                        "availability": {
                            "edit":
                                genRowCol([info.timeText, props.availability_code_display, props.user_display, props.location_display], 2),
                            "schedule":
                                `${props.user_display}<br>${props.location_display}`,
                        },
                        "appointment": {
                            "my":
                                genRowCol([info.timeText, props.record_display, props.visit_display, props.location_display], 2),
                            "schedule":
                                genRowCol([info.timeText, props.record_display, props.visit_display, props.user_display, props.location_display], 2)
                        },
                    }[type][Page.type]
                }
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
                    all_appointments: Page.type == "my",
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

                let availabilityPromise = Promise.resolve([])
                if (["schedule", "edit"].includes(Page.type) && Calendar._showAvailability)
                    availabilityPromise = API.getAvailability({ ...paramsCommon, ...paramsAvailability })

                let appointmentPromise = Promise.resolve([])
                if (["schedule", "my"].includes(Page.type))
                    appointmentPromise = API.getAppointments({ ...paramsCommon, ...paramsAppointment })

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