import Swal from 'sweetalert2'
import html from './html/userConfig.html'
import PopOver from "./popover"

const defaultStart = "05:00"
const defaultEnd = "18:00"
const defaultSlotSize = "30"
const defaultHiddenDays = [0, 6] //Sunday & Saturday
const defaultExpandRows = true
const defaultLimitAvailability = true

const rcBtnColor = getComputedStyle($.getElementById("content")).getPropertyValue("--redcap-btn-color")
class UserConfig {

    static _init = false

    static get() {
        const expandRows = localStorage.getItem("expandRows")
        const limitAvailability = localStorage.getItem("limitAvailability")
        let hiddenDays = localStorage.getItem("configDays").split(',').filter(x => x)
        hiddenDays = hiddenDays ? hiddenDays.map(x => parseInt(x)) : defaultHiddenDays
        const showAllDays = localStorage.getItem("showAllDays") == "true"
        hiddenDays = showAllDays ? [] : hiddenDays
        return {
            start: localStorage.getItem("configStart") || defaultStart,
            end: localStorage.getItem("configEnd") || defaultEnd,
            hiddenDays: hiddenDays || defaultHiddenDays,
            slotSize: localStorage.getItem("slotSize") || defaultSlotSize,
            expandRows: typeof expandRows === "string" ? expandRows === "true" : defaultExpandRows,
            limitAvailability: typeof limitAvailability === "string" ? limitAvailability === "true" : defaultLimitAvailability
        }
    }

    static init() {
        if (UserConfig._init) return
        // Setup toggles for the week bar 
        $.addEventListener("click", (event) => {
            let classList = event.target.classList
            if (!classList.contains("configWeek")) return;
            event.target.classList.toggle("btn-primary")
            event.target.classList.toggle("btn-danger")
        })
        UserConfig._init = true
    }

    static open() {

        const { start, end, hiddenDays, slotSize, expandRows, limitAvailability } = UserConfig.get()
        UserConfig.init()

        // Modify the html with current values
        const newHtml = html.replace("START-TIME", start).replace("END-TIME", end).
            replace("SLOT-SIZE", slotSize).replace("CHECKED-EXPAND", expandRows ? "checked" : "").
            replace("CHECKED-LIMIT", limitAvailability ? "checked" : "")

        PopOver.close()
        Swal.fire({
            title: "User Configuration",
            html: newHtml,
            confirmButtonColor: rcBtnColor,
            confirmButtonText: "Save",
        }).then((result) => {

            // Bail if save wasn't clicked
            if (!result.isConfirmed) return

            // Save everything back to local storage
            localStorage.setItem("configStart", $.getElementById("configStart").value)
            localStorage.setItem("configEnd", $.getElementById("configEnd").value)
            localStorage.setItem("slotSize", $.getElementById("slotSize").value)
            localStorage.setItem("expandRows", $.getElementById("expandRows").checked)
            localStorage.setItem("limitAvailability", $.getElementById("limitAvailability").checked)
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