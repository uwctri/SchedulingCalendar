import Page from "./page"
import { DateTime } from "luxon"
import RedCap from "./redcap"
import API from "./api"
class ICS {

    static open() {
        // TODO open a real modal for Cron Sched
        // TODO have option for extra info in the export
        ICS.export()
    }

    static export() {

        const downloadString = (filename, text) => {
            let el = $.createElement('a');
            el.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
            el.setAttribute('download', filename);
            el.style.display = 'none';
            $.body.appendChild(el);
            el.click();
            $.body.removeChild(el);
        }

        const makeUrl = (record) => {
            return location.href.split("ExternalModules")[0] + `record_home.php?pid={Page.pid}&id=${record}`
        }

        let ics = `
            BEGIN:VCALENDAR
            VERSION:2.0
            PRODID:-//CTRI/REDCap Schedule//NONSGML v1.0//EN
            X-WR-CALNAME:REDCap Schedule Export - ${Page.pid}
            `.trim();

        API.getAppointments({
            start: DateTime.now().minus({ days: 30 }).toISO(),
            end: DateTime.now().plus({ days: 60 }).toISO(),
            providers: [],
            locations: [],
            subjects: [],
            visits: [],
            all_appointments: false,
        }).then((appts) => {
            appts.forEach(data => {
                let extra = "" // TODO
                let link = makeUrl(data.record)
                ics += `
                    BEGIN:VEVENT
                    UID:${data.user}-${data.record}-${data.visit}
                    DTSTAMP:${data.start}
                    ORGANIZER;CN=REDCap:MAILTO:${RedCap.email}
                    DTSTART:${data.start}
                    DTEND:${data.end}
                    SUMMARY:${RedCap.project_name}-${data.user_display}
                    DESCRIPTION:Study: ${RedCap.project_name}\\nProvider: ${data.user_display}\\nSubject: ${data.record_display}\\nVisit: ${data.visit_display}\\n${extra}Link: ${link}
                    END:VEVENT`;
            });

            ics += `\nEND:VCALENDAR`;
            downloadString(`${RedCap.project_name} Calendar.ics`, ics.replace(/ {4}/g, ''));
        })
    }
}

export default ICS