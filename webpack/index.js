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
import { CRUD, Resource } from "./enums"
import "./iconObserver"
import "./style.less"

// Load user config and FC toolbar
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

// Setup quick search hotkey
document.addEventListener("keyup", (event) => {
    if (event.key == "s" && !SearchBar.isVisible() && SearchBar.isReady()) {
        document.getElementsByClassName("fc-search-button")[0].click()
        SearchBar.focus()
    }
})

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
        if (["singleMonth", "singleWeek"].includes(calendar.view.type) && pageURL.type == "edit") {
            const eventId = arg.event.id
            arg.el.addEventListener("contextmenu", (jsEvent) => {
                jsEvent.preventDefault()
                console.log("contextMenu", eventId)
                // TODO
            })
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
        const title = `${info.timeText}<br>${info.event.title}`
        return { html: title };
    },
    eventSources: [
        {
            url: router,
            method: "POST",
            extraParams: () => {
                return {
                    redcap_csrf_token: csrf,
                    crud: CRUD.Read,
                    resource: Resource.Availability, // TODO
                    page: pageURL.type,
                    providers: [],
                    locations: [],
                    subjects: [],
                    events: [],
                }
            }
        }
    ]
})

document.getElementById("content").classList.remove("d-none")
document.getElementById("pageMenu").classList.remove("d-none")
calendar.render()
SearchBar.build()
setInterval(() => calendar.refetchEvents(), 1000 * 60 * 2)
