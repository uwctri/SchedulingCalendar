import { DateTime } from "luxon"
import IMask from "imask"
import API from "./api"
import RedCap from "./redcap"
import template_availability from "./html/availability_popup.html"
import template_appointment from "./html/appointment_popup.html"
import template_details from "./html/details_popup.html"
import Calendar from "./calendar"
import Page from "./page"
import { buildGroupDropdown, buildLocationDropdown, buildProviderDropdown, buildVisitDropdown, buildSubjectDropdown } from "./utils"

const closeBtn = `<span class="close" id="PopClose">&times;</span>`
const saveDelay = 2000 // Time to wait before closing the popover after saving
const loadingDots = `<div class="loading-dots"></div>`
const html_availability = RedCap.ttHTML(template_availability)
const html_appointment = RedCap.ttHTML(template_appointment)
const html_details = RedCap.ttHTML(template_details)
class PopOver {

    static _date = null
    static _setup = false
    static _open = false

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
        $.addEventListener("click", (e) => {
            if (e.target.id == "PopClose")
                PopOver.close()
            if (e.target.id !== "aPopAddBtn")
                return
            if (!PopOver.validate())
                return

            let start = DateTime.fromFormat($.getElementById("aPopStartTime").value, "hh:mm a").toISOTime()
            start = PopOver._date.toISODate() + "T" + start
            let end = DateTime.fromFormat($.getElementById("aPopEndTime").value, "hh:mm a").toISOTime()
            end = PopOver._date.toISODate() + "T" + end

            if (Page.type == "edit") {
                API.setAvailability({
                    "providers": $.getElementById("aPopProvider").value,
                    "locations": $.getElementById("aPopLocation").value,
                    "group": $.getElementById("aPopGroup").value,
                    "start": start,
                    "end": end,
                }).then(data => {
                    Calendar.refresh()
                })
            } else if (Page.type == "schedule") {
                API.setAppointments({
                    "visits": $.getElementById("aPopVisit").value,
                    "providers": $.getElementById("aPopProvider").value,
                    "locations": $.getElementById("aPopLocation").value,
                    "subjects": $.getElementById("aPopSubject").value,
                    "notes": $.getElementById("aPopNotes").value,
                    "start": start,
                    "end": end,
                }).then(data => {
                    Calendar.refresh()
                })
            }

            $.getElementById("aPopAddBtn").innerHTML = loadingDots
            setTimeout(PopOver.close, saveDelay)
        })
        PopOver._setup = true
    }

    static validate() {
        PopOver.clearValidation()
        let els = $.querySelectorAll(".popover input, .popover select")
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
        $.querySelectorAll(".popover .is-invalid").forEach(e => e.classList.remove("is-invalid"))
        $.querySelectorAll(".popover .is-invalid-noicon").forEach(e => e.classList.remove("is-invalid-noicon"))
    }

    static openAvailability(info) {
        const title = `${RedCap.tt("pop_new_avail")} ${closeBtn}`
        PopOver.openPopover(title, html_availability, info.jsEvent.target)
        PopOver._date = DateTime.fromISO(info.startStr)

        // IMask is used for input masking and not native Bootstrap due to sizing
        // issues inside the popover.
        const startTime = $.getElementById("aPopStartTime")
        startTime.value = DateTime.fromISO(info.startStr).toFormat("hh:mm a")
        IMask(startTime, PopOver.timeMask12)

        const endTime = $.getElementById("aPopEndTime")
        endTime.value = DateTime.fromISO(info.endStr).toFormat("hh:mm a")
        IMask(endTime, PopOver.timeMask12)

        buildGroupDropdown("aPopGroup", PopOver.isOpen)
        buildLocationDropdown("aPopLocation", PopOver.isOpen)
        buildProviderDropdown("aPopProvider", PopOver.isOpen)
    }

    static openScheduleVisit(info) {
        const title = `${RedCap.tt("pop_new_sch")} ${closeBtn}`
        PopOver.openPopover(title, html_appointment, info.jsEvent.target)
        PopOver._date = DateTime.fromISO(info.startStr)

        // IMask is used for input masking and not native Bootstrap due to sizing
        // issues inside the popover.
        const startTime = $.getElementById("aPopStartTime")
        startTime.value = DateTime.fromISO(info.startStr).toFormat("hh:mm a")
        IMask(startTime, PopOver.timeMask12)

        const endTime = $.getElementById("aPopEndTime")
        endTime.value = DateTime.fromISO(info.endStr).toFormat("hh:mm a")
        IMask(endTime, PopOver.timeMask12)

        buildVisitDropdown("aPopVisit", null, null, PopOver.isOpen)
        buildLocationDropdown("aPopLocation", PopOver.isOpen)
        buildProviderDropdown("aPopProvider", PopOver.isOpen)
        buildSubjectDropdown("aPopSubject", PopOver.isOpen)

        const enforceDuration = () => {
            const visit = $.getElementById("aPopVisit").value
            const config = API.cache.visits.data[visit]
            let start = DateTime.fromFormat($.getElementById("aPopStartTime").value, "hh:mm a")
            endTime.disabled = false

            if (!visit)
                return

            // Set duration
            if (config.duration) {
                endTime.value = start.plus({ minutes: config.duration }).toFormat("hh:mm a")

                // Not Extendable
                if (!config.isExtendable)
                    endTime.disabled = true
            }
        }

        $.getElementById("aPopStartTime").addEventListener("change", () => {
            enforceDuration()
            // TODO is provider still available at that time?
        })

        $.getElementById("aPopEndTime").addEventListener("change", () => {
            // TODO is provider still available at that time?
        })

        $.getElementById("aPopVisit").addEventListener("change", () => {
            enforceDuration()
        })

        $.getElementById("aPopSubject").addEventListener("change", () => {
            const subject = $.getElementById("aPopSubject").value

            // Rebuild the visit list with only those visits the subject hasn't had
            const selEl = $.getElementById("aPopVisit")
            const visit = selEl.value;
            [...selEl.options].slice(1).forEach(e => e.remove())
            buildVisitDropdown("aPopVisit", subject, visit, PopOver.isOpen)

            // Default the location 
            const defLoc = API.cache.subjects.data[subject].location // default loc
            const locs = [...document.getElementById("aPopLocation").options].map(el => el.value)
            if (locs.includes(defLoc))
                $.getElementById("aPopLocation").value = defLoc
        })
    }

    static openDetails(info) {
        const title = `${RedCap.tt("pop_details")} ${closeBtn}`
        const props = info.event.extendedProps
        const start = DateTime.fromISO(info.event.startStr).toFormat("hh:mm a")
        const end = DateTime.fromISO(info.event.endStr).toFormat("hh:mm a")
        const html = html_details.replace("Example Time", `${start} - ${end}`)
            .replace("Example Subject", props.record_display).replace("Example Visit", props.visit_display)
            .replace("Example Provider", props.user_display).replace("Example Location", props.location_display)
            .replace("Example Notes", props.notes).replace("d-none", props.notes ? "" : "d-none")
        PopOver.openPopover(title, html, info.jsEvent.target)
        PopOver._date = null
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
    }

    static isOpen() {
        return PopOver._open
    }

    static close() {
        PopOver._open = false
        $.querySelectorAll(".popover").forEach(e => e.remove())
    }

}

export default PopOver