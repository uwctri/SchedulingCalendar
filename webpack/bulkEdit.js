import Swal from 'sweetalert2'
import template from './html/bulkEdit.html'
import API from "./api"
import { DateTime } from 'luxon'
import { buildGroupDropdown, buildLocationDropdown, buildProviderDropdown, savingAnimation } from "./utils"
import Calendar from './calendar'
import Litepicker from 'litepicker'
import 'litepicker/dist/plugins/multiselect';
import PopOver from "./popover"
import RedCap from "./redcap"
import { CRUD, Resource } from "./enums"
import UserConfig from './userConfig'

const modalWidth = "800px"
const defaultStart = "08:00"
const defaultEnd = "17:00"
const html = RedCap.ttHTML(template)
class BulkEdit {

    static picker = null
    static pickType = UserConfig.get()["bulkPickerType"]

    static get() {
        const skipWeekend = $.getElementById("bulkEditSkip").checked
        const startDay = DateTime.fromJSDate(BulkEdit.picker.getStartDate()?.toJSDate())
        const endDay = DateTime.fromJSDate(BulkEdit.picker.getEndDate()?.toJSDate())
        let dates = []
        if (BulkEdit.pickType == "range") {
            for (let date = startDay; date <= endDay; date = date.plus({ days: 1 })) {
                if (skipWeekend && (date.weekday == 6 || date.weekday == 7))
                    continue
                dates.push(date)
            }
        } else {
            dates = BulkEdit.picker.getMultipleDates().map(date => DateTime.fromJSDate(date.toJSDate()))
        }
        return {
            dates: dates,
            startDay: startDay,
            endDay: endDay,
            start: $.getElementById("bulkEditStart").value,
            end: $.getElementById("bulkEditEnd").value,
            group: $.getElementById("bulkEditGroup").value,
            location: $.getElementById("bulkEditLocation").value,
            provider: $.getElementById("bulkEditProvider").value,
        }
    }

    static open() {

        const sendBundle = (crud, o) => {
            let bundle = []
            for (let date of o.dates) {
                bundle.push({
                    "providers": o.provider,
                    "locations": o.location,
                    "group": o.group,
                    "start": `${date.toFormat('yyyy-MM-dd')}T${o.start}:00`,
                    "end": `${date.toFormat('yyyy-MM-dd')}T${o.end}:00`,
                })
            }
            API.multi({
                "crud": crud,
                "resource": Resource.Availability,
                "bundle": bundle
            }).then(data => {
                Calendar.refresh()
            })
        }

        const updatePicker = () => {
            const isMulti = BulkEdit.pickType == "multi"
            $.getElementById("bulkEditSkip").disabled = isMulti
            $.getElementById("bulkEditSkip").checked = isMulti ? false : $.getElementById("bulkEditSkip").checked
            $.getElementByClassName("calTypeRange").style.fontWeight = !isMulti ? "bold" : "normal"
            $.getElementByClassName("calTypeMulti").style.fontWeight = isMulti ? "bold" : "normal"
            BulkEdit.setupPicker()
        }

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
                sendBundle(CRUD.Create, o)

                savingAnimation(btnEl)
                setTimeout(Swal.close, 2000)
                return false
            },
            preDeny: () => {
                if (!BulkEdit.validate())
                    return false;

                const btnEl = "swal2-deny"
                const o = BulkEdit.get()

                if (BulkEdit.pickType == "range") {
                    API.deleteAvailability({
                        "providers": o.provider,
                        "locations": o.location,
                        "group": o.group,
                        "start": `${o.startDay.toFormat('yyyy-MM-dd')}T${o.start}:00`,
                        "end": `${o.endDay.toFormat('yyyy-MM-dd')}T${o.end}:00`,
                    }).then(data => {
                        Calendar.refresh()
                    })
                } else {
                    sendBundle(CRUD.Delete, o)
                }

                savingAnimation(btnEl)
                setTimeout(Swal.close, 2000)
                return false
            }
        })

        $.getElementById("bulkEditMe").addEventListener("click", () => setProviderCurrentUser("bulkEditProvider"))

        $.getElementByClassName("bulkEditCalType").addEventListener("click", (event) => {
            const newType = BulkEdit.pickType == "range" ? "multi" : "range"
            UserConfig.set("bulkPickerType", newType)
            BulkEdit.pickType = newType
            updatePicker()
        })

        updatePicker()
    }

    static setupPicker() {
        if (BulkEdit.picker)
            BulkEdit.picker.destroy()

        const settings = {
            element: $.getElementById('litepicker'),
            inlineMode: true,
            singleMode: false,
            firstDay: 0,
        }
        BulkEdit.picker = new Litepicker(
            Object.assign(settings, BulkEdit.pickType == "range" ? {} : {
                plugins: ['multiselect']
            })
        )
    }

    static init() {
        BulkEdit.setupPicker()
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
        if (((BulkEdit.pickType == "range") && (BulkEdit.picker.getStartDate() == null || BulkEdit.picker.getEndDate() == null)) ||
            ((BulkEdit.pickType == "multi") && BulkEdit.picker.getMultipleDates().length == 0)) {
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