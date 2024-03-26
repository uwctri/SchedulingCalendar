// This file uses the popper.js library that ships with Bootstrap and RC
// jQuery is avoided except when necessary

import { DateTime } from "luxon"
import IMask from "imask";
import API from "./api"
import Loading from "./loading"
import html_availability from "./html/availability_popup.html"

class PopOver {

    static _date = null;
    static _setup = false;
    static _open = false;
    static closeBtn = `<span class="close" id="PopClose">&times;</span>`
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

    static setup() {
        if (PopOver._setup) return
        document.addEventListener("click", (e) => {
            if (e.target.id !== "aPopAddBtn")
                return
            if (!PopOver.validate())
                return

            let start = DateTime.fromFormat(document.getElementById("aPopStartTime").value, "hh:mm a").toISOTime()
            start = PopOver._date.toISODate() + "T" + start
            let end = DateTime.fromFormat(document.getElementById("aPopEndTime").value, "hh:mm a").toISOTime()
            end = PopOver._date.toISODate() + "T" + end

            API.setAvailability({
                "provider": document.getElementById("aPopProvider").value,
                "location": document.getElementById("aPopLocation").value,
                "group": document.getElementById("aPopGroup").value,
                "start": start,
                "end": end,
            }).then(data => {
                // TODO maybe show a saving animation before closing?
                calendar.refetchEvents()
            })
            PopOver.close()
        })
        PopOver._setup = true
    }

    static validate() {
        PopOver.clearValidation()
        let els = document.querySelectorAll(".popover input, .popover select")
        let valid = true
        for (const el of els) {
            const tmp = el.value.replaceAll(/[:_ ]/g, '')
            if (el.value === "" || tmp === "") {
                el.classList.add(el.tagName == "SELECT" ? "is-invalid" : "is-invalid-noicon")
                valid = false
            }
        }
        return valid
    }

    static clearValidation() {
        document.querySelectorAll(".popover .is-invalid").forEach(e => e.classList.remove("is-invalid"))
        document.querySelectorAll(".popover .is-invalid-noicon").forEach(e => e.classList.remove("is-invalid-noicon"))
    }

    static openAvailability(info) {
        let title = `Adding New Availability ${PopOver.closeBtn}`
        PopOver.openPopover(title, html_availability, info.jsEvent.target)
        PopOver._date = DateTime.fromISO(info.startStr)

        const startTime = document.getElementById("aPopStartTime")
        startTime.value = DateTime.fromISO(info.startStr).toFormat("hh:mm a")
        const startMask = IMask(startTime, PopOver.timeMask12)

        const endTime = document.getElementById("aPopEndTime")
        endTime.value = DateTime.fromISO(info.endStr).toFormat("hh:mm a")
        const endMask = IMask(endTime, PopOver.timeMask12)

        // Build out the group codes
        API.availabilityCodes().then(data => {
            if (!PopOver.isOpen()) return
            const select = document.getElementById("aPopGroup")
            for (const k in data) {
                let option = document.createElement("option")
                option.value = data[k].value
                option.text = data[k].label
                select.add(option)
            }
        })

        // Build out locations
        API.locations().then(locations => {
            if (!PopOver.isOpen()) return
            // TODO Some locations should be filtered out
            const select = document.getElementById("aPopLocation")
            const loopOver = (obj) => {
                for (const code in obj) {
                    if (obj[code].sites)
                        loopOver(obj[code].sites)
                    if (!obj[code].active)
                        continue
                    let option = document.createElement("option")
                    option.value = code
                    option.text = obj[code].name
                    select.add(option)
                }
            }
            loopOver(locations)
        })

        // Build out the providers
        if (!user.isCalendarAdmin) {
            const select = document.getElementById("aPopProvider")
            let option = document.createElement("option")
            option.value = user.username
            option.text = user.name
            option.selected = true
            select.add(option)
        } else {
            API.providers().then(providers => {
                if (!PopOver.isOpen()) return
                // TODO Some providers are unschedulable
                const select = document.getElementById("aPopProvider")
                for (const k in providers) {
                    let option = document.createElement("option")
                    option.value = providers[k].value
                    option.text = providers[k].label
                    select.add(option)
                }
            })
        }
    }

    static openPopover(title, content, target) {
        PopOver.setup()
        PopOver.close()
        PopOver._open = true
        jQuery(target).popover({
            title: title,
            content: content,
            html: true,
            sanitize: false,
            container: "body",
        }).popover("show");
        document.getElementById("PopClose").addEventListener("click", PopOver.close)
    }

    static isOpen() {
        return PopOver._open
    }

    static close() {
        PopOver._open = false
        document.querySelectorAll(".popover").forEach(e => e.remove())
    }

}

export default PopOver