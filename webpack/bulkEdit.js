import Swal from 'sweetalert2'
import html from './html/bulkEdit.html'
import API from "./api"
import { DateTime } from 'luxon';
import { buildGroupDropdown, buildLocationDropdown, buildProviderDropdown } from "./utils"
import Litepicker from 'litepicker';
import PopOver from "./popover"

const modalWidth = "800px"
const defaultStart = "08:00"
const defaultEnd = "17:00"
class BulkEdit {

    static picker = null

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
            preConfirm: BulkEdit.validate,
            preDeny: BulkEdit.validate,
        }).then((result) => {

            if (!result.isConfirmed && !result.isDenied)
                return

            // TODO validate these two dates
            let startDay = DateTime.fromJSDate(BulkEdit.picker.getStartDate().toJSDate())
            let endDay = DateTime.fromJSDate(BulkEdit.picker.getEndDate().toJSDate())
            let start = document.getElementById("bulkEditStart").value
            let end = document.getElementById("bulkEditEnd").value
            const group = document.getElementById("bulkEditGroup").value
            const location = document.getElementById("bulkEditLocation").value
            const provider = document.getElementById("bulkEditProvider").value
            const skipWeekend = document.getElementById("bulkEditSkip").checked

            if (result.isConfirmed) {
                let bundle = []
                for (let date = startDay; date <= endDay; date = date.plus({ days: 1 })) {
                    if (skipWeekend && (date.weekday == 6 || date.weekday == 7))
                        continue
                    bundle.push({
                        "provider": provider,
                        "location": location,
                        "group": group,
                        "start": `${date.toFormat('yyyy-MM-dd')}T${start}:00`,
                        "end": `${date.toFormat('yyyy-MM-dd')}T${end}:00`,
                    })
                }
                console.log(bundle)
                // TODO need a setBulkAvailability endpoint
                // API.setBulkAvailability({
                //     "bundle": bundle
                // }).then(data => {
                //     calendar.refetchEvents()
                // })
            }

            if (result.isDenied) {
                // TODO Allow for removal (with a check before)
                // TODO need a deleteBulkAvailability endpoint
            }

            // TODO Add a saving animation
        })
    }

    static initModal() {
        BulkEdit.picker = new Litepicker({
            element: document.getElementById('litepicker'),
            inlineMode: true,
            singleMode: false,
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

}

export default BulkEdit