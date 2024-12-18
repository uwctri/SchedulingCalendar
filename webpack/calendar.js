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
import ColorConfig from "./colorConifg"
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
    static _metadata = {}
    static _userColors = {}

    static toolbars = {
        edit: {
            topRight: ["search", "singleMonth,singleWeek,singleDay"],
            topLeft: ["prev,next", "today", "config", "lock", "bulk"],
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
    static gotoDate = (date) => { Calendar._fc.gotoDate(date) }

    static showLoading() {
        $.getElementById("loader").classList.remove("d-none")
    }

    static hideLoading() {
        $.getElementById("loader").classList.add("d-none")
    }

    static isLoading() {
        return !$.getElementById("loader").classList.contains("d-none")
    }

    static init() {

        // Every 60 seconds check to see if a hard pull has occured in the past N minutes
        // and pull if not
        setInterval(() => {
            if (DateTime.now().diff(Calendar._refreshTime).seconds >= autoRefreshTime)
                Calendar.refresh()
        }, 1000 * 60)

        // Grab any admin set metadata
        API.metadata().then(metadata => {
            Calendar._metadata = metadata.data
        })

        // Modify toolbars
        Calendar.toolbars = Calendar.toolbars[Page.type]
        Calendar.toolbars.bottomRight = Page.refer ? ["refer"] : Calendar.toolbars.bottomRight
        Calendar.toolbars.bottomLeft = RedCap.user.isCalendarAdmin ? ["cleanup", "ics,icsDownload,icsCopy", "userColors"] : Calendar.toolbars.bottomLeft

        // Grab user settings
        const { start: startTime, end: endTime, hiddenDays, slotSize, expandRows, lineHeight, limitAvailability } = UserConfig.get()

        Calendar._fc = new FullCalendar($.getElementById("calendar"), {
            plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin],
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
            customButtons: {
                today: {
                    text: RedCap.tt("btn_today"),
                    click: () => Calendar._fc.today()
                },
                cleanup: {
                    icon: "fa-broom",
                    hint: RedCap.tt("alt_cleanup"),
                    click: CleanUp.open
                },
                ics: {
                    text: ".ICS",
                },
                icsDownload: {
                    icon: "fa-download",
                    hint: RedCap.tt("alt_ics"),
                    click: ICS.export
                },
                icsCopy: {
                    icon: "fa-copy",
                    hint: RedCap.tt("alt_ics_copy"),
                    click: ICS.copyLink
                },
                config: {
                    icon: "fa-gear",
                    hint: RedCap.tt("alt_user"),
                    click: UserConfig.open
                },
                userColors: {
                    icon: "fa-palette",
                    hint: RedCap.tt("alt_colors"),
                    click: ColorConfig.open
                },
                search: {
                    icon: "fa-magnifying-glass",
                    hint: RedCap.tt("alt_search"),
                    click: SearchBar.toggle
                },
                refer: {
                    text: RedCap.tt("btn_return"),
                    click: () => {
                        if (Page.refer.startsWith("http"))
                            location.href = Page.refer
                        location.href = $.referrer
                    }
                },
                bulk: {
                    text: RedCap.tt("btn_bulk"),
                    click: BulkEdit.open
                },
                lock: {
                    icon: "fa-lock",
                    hint: RedCap.tt("btn_lock"),
                    click: () => {
                        const newSetting = !Calendar._fc.getOption("editable")
                        const o = !newSetting ? ["fa-unlock", "fa-lock"] : ["fa-lock", "fa-unlock"]
                        Calendar._fc.setOption("editable", newSetting)
                        Calendar._fc.setOption("eventResizableFromStart", newSetting)
                        $.querySelector(".fc-lock-button ." + o[0]).classList.replace(o[0], o[1])
                    }
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
            initialDate: Page.date,
            slotDuration: `00:${slotSize}:00`,
            unselectAuto: false,
            navLinks: true,
            editable: false, // Set Dynamically
            eventResizableFromStart: false, // Set Dynamically
            dayMaxEvents: true,
            initialView: "singleWeek",
            slotMinTime: startTime,
            slotMaxTime: endTime,
            expandRows: expandRows,
            selectable: Page.type != "my",
            datesSet: (dateInfo) => {
                const newStart = DateTime.fromISO(dateInfo.startStr).toFormat("yyyy-MM-dd")
                for (const type of ["my", "schedule", "edit"]) {
                    const typeLink = $.getElementByClassName(`type-${type}`).href.split("&date")[0]
                    $.getElementByClassName(`type-${type}`).href = `${typeLink}&date=${newStart}`
                }
            },
            dateClick: (dateClickInfo) => {
                if (dateClickInfo.view.type == "singleMonth") {
                    Calendar._fc.changeView("singleWeek")
                    Calendar.gotoDate(dateClickInfo.date)
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
            eventResize: (info) => {
                API.updateAvailability({
                    id: info.el.getAttribute("data-internal-id"),
                    start: DateTime.fromJSDate(info.event.start).toFormat("yyyy-MM-dd HH:mm:ss"),
                    end: DateTime.fromJSDate(info.event.end).toFormat("yyyy-MM-dd HH:mm:ss")
                }).then((data) => {
                    Calendar.refresh()
                })
            },
            eventDrop: (info) => {
                API.updateAvailability({
                    id: info.el.getAttribute("data-internal-id"),
                    start: DateTime.fromJSDate(info.event.start).toFormat("yyyy-MM-dd HH:mm:ss"),
                    end: DateTime.fromJSDate(info.event.end).toFormat("yyyy-MM-dd HH:mm:ss")
                }).then((data) => {
                    Calendar.refresh()
                })
            },
            eventContent: (info) => {
                const props = info.event.extendedProps
                const type = props.is_availability ? "availability" : "appointment"
                const view = Calendar.getView()
                let title = "Missing Title"

                if (view == "agenda") {
                    // Agenda view, only on Scheduling/My page, won't show anything but appts
                    title = `${props.record_display} - ${props.visit_display}<br>${props.user_display}<br>${props.location_display}`
                } else if (view == "singleMonth" && Page.type != "edit") {
                    // Month, appointments only
                    title = `${info.timeText} ${props.record_display}<br>${props.visit_display} | ${props.user_display}<br>${props.location_display}`
                } else if (view == "singleMonth" && Page.type == "edit") {
                    // Month, availability on the edit page only
                    title = `${info.timeText} ${props.user_display}<br>${props.location_display}`
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
                    start: DateTime.fromISO(info.startStr).toFormat("yyyy-MM-dd HH:mm:ss"),
                    end: DateTime.fromISO(info.endStr).toFormat("yyyy-MM-dd HH:mm:ss"),
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
                    const user = calEvent.user
                    const color = Calendar._userColors[user] || Calendar._metadata[user]?.color || ColorConfig.getRandomAccessableColor()
                    calEvent.color = color
                    Calendar._userColors[user] = color
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