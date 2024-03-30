import Swal from 'sweetalert2'
import html from './html/bulkEdit.html'
import API from "./api"
import { DateTime } from 'luxon';
import { CRUD, Resource } from "./enums"
import { buildGroupDropdown, buildLocationDropdown, buildProviderDropdown } from "./utils"
import Litepicker from 'litepicker';
import PopOver from "./popover"

const modalWidth = "800px"
const defaultStart = "08:00"
const defaultEnd = "17:00"
const loadingDots = `<div class="loading-dots"></div>`
class BulkEdit {

    static picker = null

    static get() {
        return {
            startDay: DateTime.fromJSDate(BulkEdit.picker.getStartDate().toJSDate()),
            endDay: DateTime.fromJSDate(BulkEdit.picker.getEndDate().toJSDate()),
            start: document.getElementById("bulkEditStart").value,
            end: document.getElementById("bulkEditEnd").value,
            group: document.getElementById("bulkEditGroup").value,
            location: document.getElementById("bulkEditLocation").value,
            provider: document.getElementById("bulkEditProvider").value,
            skipWeekend: document.getElementById("bulkEditSkip").checked
        }
    }

    static open() {

        PopOver.close()
        Swal.fire({
            title: "Bulk Availability Edit",
            html: html,
            showDenyButton: true,
            denyButtonText: "Remove",
            confirmButtonText: "Add", // TODO fix this button color
            customClass: {
                container: 'bulkEditModal'
            },
            didOpen: BulkEdit.initModal,
            width: modalWidth,
            preConfirm: () => {
                if (!BulkEdit.validate)
                    return false

                const btnEl = "swal2-confirm"
                const o = BulkEdit.get()

                let bundle = []
                for (let date = o.startDay; date <= o.endDay; date = date.plus({ days: 1 })) {
                    if (o.skipWeekend && (date.weekday == 6 || date.weekday == 7))
                        continue
                    bundle.push({
                        "provider": o.provider,
                        "location": o.location,
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
                    calendar.refetchEvents()
                })

                BulkEdit.savingAnimation(btnEl)
                setTimeout(Swal.close, 2000)
                return false
            },
            preDeny: () => {
                if (!BulkEdit.validate)
                    return false;

                const btnEl = "swal2-deny"
                const o = BulkEdit.get()

                // TODO Allow for removal via a range
                API.deleteAvailability({
                    "provider": o.provider,
                    "location": o.location,
                    "group": o.group,
                    "start": `${o.startDay.toFormat('yyyy-MM-dd')}T${o.start}:00`,
                    "end": `${o.endDay.toFormat('yyyy-MM-dd')}T${o.end}:00`,
                }).then(data => {
                    calendar.refetchEvents()
                })

                BulkEdit.savingAnimation(btnEl)
                setTimeout(Swal.close, 2000)
                return false
            }
        })
    }

    static initModal() {
        BulkEdit.picker = new Litepicker({
            element: document.getElementById('litepicker'),
            inlineMode: true,
            singleMode: false,
            firstDay: 0,
        })
        document.getElementById("bulkEditStart").value = defaultStart
        document.getElementById("bulkEditEnd").value = defaultEnd

        const addOption = (el, text) => {
            let option = document.createElement("option")
            option.value = "*"
            option.text = text
            document.getElementById(el).add(option)
        }

        const checkBothDropdowns = (event) => {
            const a = document.getElementById("bulkEditGroup").value
            const b = document.getElementById("bulkEditLocation").value
            document.getElementsByClassName("swal2-confirm")[0].disabled = (a == "*" || b == "*")
        }

        addOption("bulkEditGroup", "Any Group (Remove Only)")
        buildGroupDropdown("bulkEditGroup", Swal.isVisible)
        document.getElementById("bulkEditGroup").addEventListener("change", checkBothDropdowns)

        addOption("bulkEditLocation", "Any Location (Remove Only)")
        buildLocationDropdown("bulkEditLocation", Swal.isVisible)
        document.getElementById("bulkEditLocation").addEventListener("change", checkBothDropdowns)

        buildProviderDropdown("bulkEditProvider", Swal.isVisible)
    }

    static validate() {
        BulkEdit.clearValidation()
        let els = document.querySelectorAll(".bulkEditModal .inputCol input, .bulkEditModal .inputCol select")
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
            document.getElementsByClassName("litepicker")[0].classList.add("litepicker-invalid")
            valid = false
        }
        return valid
    }

    static clearValidation() {
        document.querySelectorAll(".bulkEditModal .is-invalid").forEach(e => e.classList.remove("is-invalid"))
        document.querySelectorAll(".bulkEditModal .is-invalid-noicon").forEach(e => e.classList.remove("is-invalid-noicon"))
        document.getElementsByClassName("litepicker")[0].classList.remove("litepicker-invalid")
    }

    static savingAnimation(el) {
        el = document.getElementsByClassName(el)[0]
        el.style.width = getComputedStyle(el).width
        el.innerHTML = loadingDots
    }
}

export default BulkEdit