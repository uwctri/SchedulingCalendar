import { Calendar } from "@fullcalendar/core"
import interactionPlugin from "@fullcalendar/interaction"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import listPlugin from "@fullcalendar/list"
import { DateTime } from "luxon"
import UserConfig from "./userConfig"
import SearchBar from "./searchBar"
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
    location.href = `${location.href}&type=edit`
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
const calendar = new Calendar(document.getElementById("calendar"), {
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
    editable: true,
    dayMaxEvents: true,
    initialView: "singleWeek",
    slotMinTime: startTime,
    slotMaxTime: endTime,
    expandRows: expandRows,
    selectable: true,
    dateClick: (dateClickInfo) => {
        if (dateClickInfo.view.type == "singleMonth") {
            calendar.changeView("singleWeek")
            calendar.gotoDate(dateClickInfo.date)
        }
    },
    select: (selectionInfo) => {
        // TODO close any open forms
    },
    selectAllow: (selectionInfo) => {

        // Prevent from selecting multiple days
        if (calendar.view.type == "singleMonth") {
            const start = DateTime.fromISO(selectionInfo.endStr)
            const end = DateTime.fromISO(selectionInfo.startStr)
            if (start.diff(end, "days").toObject().days > 1) {
                return false
            }
        }

        // TODO can we allow selction as non-continuous square of time?
        // TODO why does SELECT also unselct stuff? Crap.
        if (calendar.view.type == "singleWeek" && pageURL.type === "edit") {
            calendar.unselect()
            let start = DateTime.fromISO(selectionInfo.startStr)
            let end = DateTime.fromISO(selectionInfo.endStr)
            for (let day = start.day; day <= end.day; day++) {
                calendar.select(start.set({ day: day }).toISO(), end.set({ day: day }).toISO())
            }
        }
        return true
    },
    unselect: function (jsEvent, view) {
        // TODO close any open forms
    },
    loading: (isLoading) => {
        if (isLoading) {
            document.getElementById("loader").classList.remove("d-none")
        } else {
            document.getElementById("loader").classList.add("d-none")
        }
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
        let title = info.timeText + "<br>" + info.event.title
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
