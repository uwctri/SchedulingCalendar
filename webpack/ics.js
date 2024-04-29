import { Page, makeRecordUrl } from "./page"
import { DateTime } from "luxon"
import RedCap from "./redcap"
import API from "./api"
class ICS {

    static downloadString(filename, text) {
        let el = $.createElement('a');
        el.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        el.setAttribute('download', filename);
        el.style.display = 'none';
        $.body.appendChild(el);
        el.click();
        $.body.removeChild(el);
    }

    static export() {
        const cal = RedCap.tt("ics_cal")
        API.post({
            utility: "ics"
        }).then(result => {
            ICS.downloadString(`${RedCap.project_name} ${cal}.ics`, result.data);
        })
    }

    static localExport() {
        const cal = RedCap.tt("ics_cal")
        const study = RedCap.tt("ics_study")
        const provider = RedCap.tt("ics_provider")
        const subject = RedCap.tt("ics_subject")
        const visit = RedCap.tt("ics_visit")
        const link = RedCap.tt("ics_link")

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
                let extra = "" // Not used in local export
                const url = makeRecordUrl(data.record)
                ics += `
                    BEGIN:VEVENT
                    UID:${data.user}-${data.record}-${data.visit}
                    DTSTAMP:${data.start}
                    ORGANIZER;CN=REDCap:MAILTO:${RedCap.email}
                    DTSTART:${data.start}
                    DTEND:${data.end}
                    SUMMARY:${RedCap.project_name}-${data.user_display}
                    DESCRIPTION:${study}: ${RedCap.project_name}\\n${provider}: ${data.user_display}\\n${subject}: ${data.record_display}\\n${visit}: ${data.visit_display}\\n${extra}${link}: ${url}
                    END:VEVENT`;
            });

            ics += `\nEND:VCALENDAR`;
            ICS.downloadString(`${RedCap.project_name} ${cal}.ics`, ics.replace(/ {4}/g, ''));
        })
    }
}

export default ICS