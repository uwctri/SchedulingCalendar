import { Calendar } from '@fullcalendar/core';
import interactionPlugin from '@fullcalendar/interaction';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import { DateTime } from "luxon";
import './style.less';

const hiddenDays = [0] // TODO
const pageURL = Object.fromEntries(new URLSearchParams(location.search))

let toolbar = ["singleMonth", "singleWeek", "singleDay"]
if (pageURL.type != "edit") {
    toolbar = ["agenda"].concat(toolbar)
}

document.addEventListener('DOMContentLoaded', () => {
    var calendarEl = document.getElementById('calendar');

    var calendar = new Calendar(calendarEl, {
        plugins: [interactionPlugin, dayGridPlugin, timeGridPlugin, listPlugin],
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: toolbar.join(',')
        },
        editable: true,
        dayMaxEvents: true, // allow "more" link when too many events
        initialView: 'singleWeek',
        slotMinTime: '05:00', // TODO make configurable
        slotMaxTime: '19:00',
        expandRows: true,
        selectable: true,
        select: (selectionInfo) => {
            // TODO close any open forms
            if (selectionInfo.view.type == 'singleMonth') {
                calendar.changeView("singleWeek");
                calendar.gotoDate(selectionInfo.start);
            }
        },
        views: {
            singleMonth: {
                type: 'dayGridMonth',
            },
            singleDay: {
                allDaySlot: false,
                type: 'timeGridDay',
            },
            singleWeek: {
                type: 'timeGridWeek',
                hiddenDays: hiddenDays, // Hide sunday/saturday
                buttonText: 'week',
                allDaySlot: false
            },
            agenda: {
                type: 'list',
                visibleRange: () => {
                    // half year forward ad back
                    let dt = DateTime.now();
                    return {
                        start: dt.minus({ day: 364 / 2 }).toJSDate(),
                        end: dt.plus({ day: 364 / 2 }).toJSDate()
                    };
                },
                listDayFormat: {
                    month: 'long',
                    year: 'numeric',
                    day: 'numeric',
                    weekday: 'long'
                },
                buttonText: 'agenda'
            }
        },
        events: [
            {
                title: 'All Day Event',
                start: '2022-01-01',
            },
            {
                title: 'Long Event',
                start: '2022-01-07',
                end: '2022-01-10'
            },
            {
                groupId: 999,
                title: 'Repeating Event',
                start: '2022-01-09T16:00:00'
            },
            {
                groupId: 999,
                title: 'Repeating Event',
                start: '2022-01-16T16:00:00'
            },
            {
                title: 'Conference',
                start: '2022-01-11',
                end: '2022-01-13'
            },
            {
                title: 'Meeting',
                start: '2022-01-12T10:30:00',
                end: '2022-01-12T12:30:00'
            },
            {
                title: 'Lunch',
                start: '2022-01-12T12:00:00'
            },
            {
                title: 'Meeting',
                start: '2022-01-12T14:30:00'
            },
            {
                title: 'Happy Hour',
                start: '2022-01-12T17:30:00'
            },
            {
                title: 'Dinner',
                start: '2022-01-12T20:00:00'
            },
            {
                title: 'Birthday Party',
                start: '2022-01-13T07:00:00'
            },
            {
                title: 'Click for Google',
                url: 'http://google.com/',
                start: '2022-01-28'
            }
        ]
    });

    calendar.render();
});