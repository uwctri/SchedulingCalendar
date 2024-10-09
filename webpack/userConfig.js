import Swal from 'sweetalert2'
import template from './html/userConfig.html'
import PopOver from "./popover"
import RedCap from './redcap'

const defaultStart = "05:00"
const defaultEnd = "18:00"
const defaultSlotSize = "30"
const defaultHiddenDays = [0, 6] //Sunday & Saturday
const defaultExpandRows = true
const defaultLimitAvailability = true
const defaultFilterToSelf = true
const defaultLineHeight = "1.5"
const defaultBulkPickerType = "range"

const html = RedCap.ttHTML(template)
class UserConfig {

    static _init = false

    static get() {
        const expandRows = localStorage.getItem("expandRows")
        const limitAvailability = localStorage.getItem("limitAvailability")
        const filterToSelf = localStorage.getItem("filterToSelf")
        let hiddenDays = localStorage.getItem("configDays")
        hiddenDays = hiddenDays ? hiddenDays.split(',').filter(x => x).map(x => parseInt(x)) : defaultHiddenDays
        const showAllDays = localStorage.getItem("showAllDays") == "true"
        hiddenDays = showAllDays ? [] : hiddenDays
        return {
            start: localStorage.getItem("configStart") || defaultStart,
            end: localStorage.getItem("configEnd") || defaultEnd,
            hiddenDays: hiddenDays || defaultHiddenDays,
            slotSize: localStorage.getItem("slotSize") || defaultSlotSize,
            expandRows: typeof expandRows === "string" ? expandRows === "true" : defaultExpandRows,
            limitAvailability: typeof limitAvailability === "string" ? limitAvailability === "true" : defaultLimitAvailability,
            filterToSelf: typeof filterToSelf === "string" ? filterToSelf === "true" : defaultFilterToSelf,
            lineHeight: localStorage.getItem("lineHeight") || defaultLineHeight,
            bulkPickerType: localStorage.getItem("bulkPickerType") || defaultBulkPickerType
        }
    }

    static set(setting, value) {
        localStorage.setItem(setting, value)
    }

    static init() {
        if (UserConfig._init) return
        // Setup toggles for the week bar 
        $.addEventListener("click", (event) => {
            let classList = event.target.classList
            if (!classList.contains("configWeek")) return
            event.target.classList.toggle("btn-primary")
            event.target.classList.toggle("btn-danger")
        })
        UserConfig._init = true
    }

    static open() {

        const { start, end, hiddenDays, slotSize, expandRows, lineHeight, limitAvailability, filterToSelf } = UserConfig.get()
        UserConfig.init()

        // Modify the html with current values
        const newHtml = html.replace("START-TIME", start).replace("END-TIME", end).
            replace("SLOT-SIZE", slotSize).replace("CHECKED-EXPAND", expandRows ? "checked" : "").
            replace("CHECKED-LIMIT", limitAvailability ? "checked" : "").
            replace("CHECKED-FILTER", filterToSelf ? "checked" : "").
            replace("LINE-HEIGHT", lineHeight)

        PopOver.close()
        Swal.fire({
            title: RedCap.tt("user_title"),
            html: newHtml,
            confirmButtonColor: RedCap.btn_color,
            confirmButtonText: RedCap.tt("save"),
            customClass: {
                container: 'userConfigModal'
            }
        }).then((result) => {

            // Bail if save wasn't clicked
            if (!result.isConfirmed) return

            // Save everything back to local storage
            localStorage.setItem("configStart", $.getElementById("configStart").value)
            localStorage.setItem("configEnd", $.getElementById("configEnd").value)
            localStorage.setItem("slotSize", $.getElementById("slotSize").value)
            localStorage.setItem("expandRows", $.getElementById("expandRows").checked)
            localStorage.setItem("lineHeight", $.getElementById("lineHeight").value)
            localStorage.setItem("limitAvailability", $.getElementById("limitAvailability").checked)
            localStorage.setItem("filterToSelf", $.getElementById("filterToSelf").checked)
            const els = $.getElementsByClassName("configWeek")
            let saveDays = []
            Array.from(els).forEach((el, index) => {
                if (!el.classList.contains("btn-danger")) return
                saveDays.push(index)
            })
            localStorage.setItem("configDays", saveDays)
            localStorage.setItem("showAllDays", saveDays.length == 0)

            // Reload Page to reinit the cal
            location.reload()
        })

        // Load days to hide values
        const els = $.getElementsByClassName("configWeek")
        Array.from(els).forEach((el, index) => {
            if (!hiddenDays.includes(index)) return
            el.classList.toggle("btn-primary")
            el.classList.toggle("btn-danger")
        })

    }

}

export default UserConfig