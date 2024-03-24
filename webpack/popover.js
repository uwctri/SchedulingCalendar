// This file uses the popper.js library that ships with Bootstrap and RC
// jQuery is avoided except when necessary

import { DateTime } from "luxon"
import IMask from "imask";
import API from "./api"
import html_availability from "./html/availability_popup.html"

class PopOver {

    static closeBtn = `<span class="close" id="PopClose" style="line-height:.7;cursor:pointer">&times;</span>`
    static timeMask12 = {
        mask: "hh:mm aa",
        lazy: false,
        blocks: {
            hh: {
                mask: IMask.MaskedRange,
                from: 1,
                to: 12
            },
            mm: {
                mask: IMask.MaskedRange,
                from: 0,
                to: 59
            },
            aa: {
                mask: IMask.MaskedEnum,
                prepareChar: str => str.toUpperCase(),
                enum: ["AM", "PM"]
            }
        }
    }
    static timeMask24 = {
        mask: "HH:mm",
        lazy: false,
        blocks: {
            HH: {
                mask: IMask.MaskedRange,
                from: 0,
                to: 23
            },
            mm: {
                mask: IMask.MaskedRange,
                from: 0,
                to: 59
            }
        }
    }

    static openAvailability(info) {
        let title = `Adding New Availability ${PopOver.closeBtn}`
        PopOver.openPopover(title, html_availability, info.jsEvent.target)

        const startTime = document.getElementById("aPopStartTime")
        startTime.value = DateTime.fromISO(info.startStr).toFormat("hh:mm a")
        const startMask = IMask(startTime, PopOver.timeMask12)

        const endTime = document.getElementById("aPopEndTime")
        endTime.value = DateTime.fromISO(info.endStr).toFormat("hh:mm a")
        const endMask = IMask(endTime, PopOver.timeMask12)

        API.availabilityCodes().then(data => {
            const select = document.getElementById("aPopGroup")
            for (const k in data) {
                let option = document.createElement("option")
                option.value = data[k].value
                option.text = data[k].label
                select.add(option)
            }
        })

        // TODO Build options for Location (filter them)
        // TODO Some providers are unschedulable

        API.providers().then(data => {
            // TODO Build options for calendar admins (all providers)
        })
        if (!user.isCalendarAdmin) {
            const select = document.getElementById("aPopProvider")
            let option = document.createElement("option")
            option.value = user.username
            option.text = user.name
            option.selected = true
            select.add(option)
        }

        // TODO Setup Click event for add button
    }

    static openPopover(title, content, target) {
        PopOver.close()
        jQuery(target).popover({
            title: title,
            content: content,
            html: true,
            sanitize: false,
            container: "body",
        }).popover("show");
        document.getElementById("PopClose").addEventListener("click", PopOver.close)
    }

    static close() {
        document.querySelectorAll(".popover").forEach(e => e.remove())
    }

}

export default PopOver