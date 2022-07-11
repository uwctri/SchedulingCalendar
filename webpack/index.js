import { Calendar } from "@fullcalendar/core"
import interactionPlugin from "@fullcalendar/interaction"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import listPlugin from "@fullcalendar/list"
import { DateTime } from "luxon"
import UserConfig from "./userConfig"
import "./observerHotfix"
import "./style.less"

const { start: startTime, end: endTime, hiddenDays, expandRows } = UserConfig.get()
const pageURL = Object.fromEntries(new URLSearchParams(location.search))

// Build out the toolbars 
let rightToolbar = ["singleMonth", "singleWeek", "singleDay"]
if (pageURL.type != "edit") {
    rightToolbar = ["agenda"].concat(rightToolbar)
}

let leftToolbar = ["prev,next", "today", "config"]

// Init the calendar
document.addEventListener("DOMContentLoaded", () => {
    var calendarEl = document.getElementById("calendar");

    var calendar = new Calendar(calendarEl, {
        plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin],
        customButtons: {
            config: {
                icon: "fa-gear",
                click: UserConfig.open
            }
        },
        headerToolbar: {
            left: leftToolbar.join(" "),
            center: "title",
            right: rightToolbar.join(",")
        },
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
                let start = DateTime.fromISO(selectionInfo.endStr)
                let end = DateTime.fromISO(selectionInfo.startStr)
                if (start.diff(end, "days").toObject().days > 1) {
                    return false
                }
            }

            // TODO can we allow selction as non-continuous square of time?
            if (calendar.view.type == "singleWeek") {

            }
            return true
        },
        unselect: function (jsEvent, view) {
            // TODO close any open forms
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
        eventSources: [
            {
                url: php.router,
                method: "POST",
                extraParams: () => {
                    return {
                        redcap_csrf_token: php.csrf,
                        method: "eventUpdate",
                        page: pageURL.type,
                        providers: [],
                    }
                }
            }
        ]
    })

    calendar.render()
})