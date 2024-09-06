import Swal from 'sweetalert2'
import template from './html/bulkEdit.html'
import API from "./api"
import { DateTime } from 'luxon'
import { buildGroupDropdown, buildLocationDropdown, buildProviderDropdown, savingAnimation } from "./utils"
import Calendar from './calendar'
import Litepicker from 'litepicker'
import PopOver from "./popover"
import RedCap from "./redcap"
import { CRUD, Resource } from "./enums"

const modalWidth = "800px"
const defaultStart = "08:00"
const defaultEnd = "17:00"
const html = RedCap.ttHTML(template)
class BulkEdit {

    static picker = null

    static get() {
        return {
            startDay: DateTime.fromJSDate(BulkEdit.picker.getStartDate()?.toJSDate()),
            endDay: DateTime.fromJSDate(BulkEdit.picker.getEndDate()?.toJSDate()),
            start: $.getElementById("bulkEditStart").value,
            end: $.getElementById("bulkEditEnd").value,
            group: $.getElementById("bulkEditGroup").value,
            location: $.getElementById("bulkEditLocation").value,
            provider: $.getElementById("bulkEditProvider").value,
            skipWeekend: $.getElementById("bulkEditSkip").checked
        }
    }

    static open() {

        PopOver.close()
        Swal.fire({
            title: RedCap.tt("bulk_title"),
            html: html,
            showDenyButton: true,
            denyButtonText: RedCap.tt("bulk_remove"),
            confirmButtonText: RedCap.tt("bulk_add"),
            customClass: {
                container: 'bulkEditModal'
            },
            didOpen: BulkEdit.init,
            width: modalWidth,
            preConfirm: () => {
                if (!BulkEdit.validate())
                    return false

                const btnEl = "swal2-confirm"
                const o = BulkEdit.get()

                let bundle = []
                for (let date = o.startDay; date <= o.endDay; date = date.plus({ days: 1 })) {
                    if (o.skipWeekend && (date.weekday == 6 || date.weekday == 7))
                        continue
                    bundle.push({
                        "providers": o.provider,
                        "locations": o.location,
                        "group": o.group,
                        "start": `${date.toFormat('yyyy-MM-dd')}T${o.start}:00`,
                        "end": `${date.toFormat('yyyy-MM-dd')}T${o.end}:00`,
                    })
                }

                API.multi({
                    "crud": CRUD.Create,
                    "resource": Resource.Availability,
                    "bundle": bundle
                }).then(data => {
                    Calendar.refresh()
                })

                savingAnimation(btnEl)
                setTimeout(Swal.close, 2000)
                return false
            },
            preDeny: () => {
                if (!BulkEdit.validate())
                    return false;

                const btnEl = "swal2-deny"
                const o = BulkEdit.get()

                API.deleteAvailability({
                    "providers": o.provider,
                    "locations": o.location,
                    "group": o.group,
                    "start": `${o.startDay.toFormat('yyyy-MM-dd')}T${o.start}:00`,
                    "end": `${o.endDay.toFormat('yyyy-MM-dd')}T${o.end}:00`,
                }).then(data => {
                    Calendar.refresh()
                })

                savingAnimation(btnEl)
                setTimeout(Swal.close, 2000)
                return false
            }
        })
    }

    static init() {
        BulkEdit.picker = new Litepicker({
            element: $.getElementById('litepicker'),
            inlineMode: true,
            singleMode: false,
            firstDay: 0,
        })
        $.getElementById("bulkEditStart").value = defaultStart
        $.getElementById("bulkEditEnd").value = defaultEnd

        const addOption = (el, text) => {
            let option = $.createElement("option")
            option.value = "*"
            option.text = text
            $.getElementById(el).add(option)
        }

        const checkBothDropdowns = (event) => {
            const a = $.getElementById("bulkEditGroup").value
            const b = $.getElementById("bulkEditLocation").value
            $.getElementsByClassName("swal2-confirm")[0].disabled = (a == "*" || b == "*")
        }

        addOption("bulkEditGroup", RedCap.tt("bulk_any_group"))
        buildGroupDropdown("bulkEditGroup", Swal.isVisible)
        $.getElementById("bulkEditGroup").addEventListener("change", checkBothDropdowns)

        addOption("bulkEditLocation", RedCap.tt("bulk_any_loc"))
        buildLocationDropdown("bulkEditLocation", Swal.isVisible)
        $.getElementById("bulkEditLocation").addEventListener("change", checkBothDropdowns)

        buildProviderDropdown("bulkEditProvider", Swal.isVisible)
    }

    static validate() {
        BulkEdit.clearValidation()
        let els = $.querySelectorAll(".bulkEditModal .inputCol input, .bulkEditModal .inputCol select")
        let valid = true
        for (const el of els) {
            if (el.type == "checkbox" || el.type == "radio")
                continue
            const tmp = el.value.replaceAll(/[:_ ]/g, '')
            if (el.value === "" || tmp === "" || el.disabled) {
                el.classList.add(el.tagName == "SELECT" ? "is-invalid" : "is-invalid-noicon")
                valid = false
            }
        }
        if (BulkEdit.picker.getStartDate() == null || BulkEdit.picker.getEndDate() == null) {
            $.getElementsByClassName("litepicker")[0].classList.add("litepicker-invalid")
            valid = false
        }
        return valid
    }

    static clearValidation() {
        $.querySelectorAll(".bulkEditModal .is-invalid").forEach(e => e.classList.remove("is-invalid"))
        $.querySelectorAll(".bulkEditModal .is-invalid-noicon").forEach(e => e.classList.remove("is-invalid-noicon"))
        $.getElementsByClassName("litepicker")[0].classList.remove("litepicker-invalid")
    }
}

export default BulkEdit