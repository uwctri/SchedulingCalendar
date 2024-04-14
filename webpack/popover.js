import { DateTime } from "luxon"
import IMask from "imask"
import API from "./api"
import RedCap from "./redcap"
import html_availability from "./html/availability_popup.html"
import html_appointment from "./html/appointment_popup.html"
import Calendar from "./calendar"
import Page from "./page"
import { buildGroupDropdown, buildLocationDropdown, buildProviderDropdown, buildVisitDropdown, buildSubjectDropdown } from "./utils";

const closeBtn = `<span class="close" id="PopClose">&times;</span>`
const saveDelay = 2000 // Time to wait before closing the popover after saving
const loadingDots = `<div class="loading-dots"></div>`
class PopOver {

    static _date = null;
    static _setup = false;
    static _open = false;
    static _animationInterval = null;

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

            if (Page.type == "edit") {
                API.setAvailability({
                    "providers": document.getElementById("aPopProvider").value,
                    "locations": document.getElementById("aPopLocation").value,
                    "group": document.getElementById("aPopGroup").value,
                    "start": start,
                    "end": end,
                }).then(data => {
                    Calendar.refresh()
                })
            } else if (Page.type == "schedule") {
                API.setAppointments({
                    "visits": document.getElementById("aPopVisit").value,
                    "providers": document.getElementById("aPopProvider").value,
                    "locations": document.getElementById("aPopLocation").value,
                    "subjects": document.getElementById("aPopSubject").value,
                    "start": start,
                    "end": end,
                }).then(data => {
                    Calendar.refresh()
                })
            }

            document.getElementById("aPopAddBtn").innerHTML = loadingDots
            setTimeout(PopOver.close, saveDelay)
        })
        PopOver._setup = true
    }

    static validate() {
        PopOver.clearValidation()
        let els = document.querySelectorAll(".popover input, .popover select")
        let valid = true
        for (const el of els) {
            if (el.type == "checkbox" || el.type == "radio")
                continue
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
        const title = `Adding New Availability ${closeBtn}`
        PopOver.openPopover(title, html_availability, info.jsEvent.target)
        PopOver._date = DateTime.fromISO(info.startStr)

        // IMask is used for input masking and not native Bootstrap due to sizing
        // issues inside the popover.
        const startTime = document.getElementById("aPopStartTime")
        startTime.value = DateTime.fromISO(info.startStr).toFormat("hh:mm a")
        IMask(startTime, PopOver.timeMask12)

        const endTime = document.getElementById("aPopEndTime")
        endTime.value = DateTime.fromISO(info.endStr).toFormat("hh:mm a")
        IMask(endTime, PopOver.timeMask12)

        buildGroupDropdown("aPopGroup", PopOver.isOpen)
        buildLocationDropdown("aPopLocation", PopOver.isOpen)
        buildProviderDropdown("aPopProvider", PopOver.isOpen)
    }

    static openScheduleVisit(info) {
        const title = `Schedule A New Visit ${closeBtn}`
        PopOver.openPopover(title, html_appointment, info.jsEvent.target)
        PopOver._date = DateTime.fromISO(info.startStr)

        // IMask is used for input masking and not native Bootstrap due to sizing
        // issues inside the popover.
        const startTime = document.getElementById("aPopStartTime")
        startTime.value = DateTime.fromISO(info.startStr).toFormat("hh:mm a")
        IMask(startTime, PopOver.timeMask12)

        const endTime = document.getElementById("aPopEndTime")
        endTime.value = DateTime.fromISO(info.endStr).toFormat("hh:mm a")
        IMask(endTime, PopOver.timeMask12)

        buildVisitDropdown("aPopVisit", PopOver.isOpen)
        buildLocationDropdown("aPopLocation", PopOver.isOpen)
        buildProviderDropdown("aPopProvider", PopOver.isOpen)
        buildSubjectDropdown("aPopSubject", PopOver.isOpen)
    }

    static openPopover(title, content, target) {
        PopOver.setup()
        PopOver.close()
        PopOver._open = true
        RedCap.popover(target, {
            title: title,
            content: content,
            html: true,
            sanitize: false,
            container: "body",
        }).popover("show")
        document.getElementById("PopClose").addEventListener("click", PopOver.close)
    }

    static isOpen() {
        return PopOver._open
    }

    static close() {
        PopOver._open = false
        if (PopOver._animationInterval) {
            clearInterval(PopOver._animationInterval)
            PopOver._animationInterval = null
        }
        document.querySelectorAll(".popover").forEach(e => e.remove())
    }

}

export default PopOver