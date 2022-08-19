import Cookies from 'js-cookie'
import Swal from 'sweetalert2'
import html from './html/userConfig.html'

const sameSite = { sameSite: 'strict' }
const defaultStart = "05:00"
const defaultEnd = "18:00"
const defaultSlotSize = "30"
const defaultHiddenDays = [0] //Sunday
const defaultExpandRows = true

// Setup toggles for the week bar 
document.addEventListener("click", (event) => {
    let classList = event.target.classList
    if (!classList.contains("configWeek")) return;
    event.target.classList.toggle("btn-primary")
    event.target.classList.toggle("btn-danger")
})

let UserConfig = {

    get: () => {
        let expandRows = Cookies.get("expandRows")
        return {
            start: Cookies.get("configStart") || defaultStart,
            end: Cookies.get("configEnd") || defaultEnd,
            hiddenDays: Cookies.get("configDays") || defaultHiddenDays,
            slotSize: Cookies.get("slotSize") || defaultSlotSize,
            expandRows: typeof expandRows === "string" ? expandRows === "true" : defaultExpandRows
        }
    },

    open: () => {

        const { start, end, hiddenDays, slotSize, expandRows } = UserConfig.get()

        // Modify the html with current values
        const newHtml = html.replace("START-TIME", start).replace("END-TIME", end).replace("SLOT-SIZE", slotSize).replace("CHECKED", expandRows ? "checked" : "")
        const btnColor = getComputedStyle(document.getElementById("content")).getPropertyValue("--redcap-btn-color")

        Swal.fire({
            title: "User Configuration",
            html: newHtml,
            confirmButtonColor: btnColor,
            confirmButtonText: "Save",
            customClass: {
                container: 'userConfigModal'
            }
        }).then((result) => {

            // Bail if save wasn't clicked
            if (!result.isConfirmed) return

            // Save everything back to cookies
            Cookies.set("configStart", document.getElementById("configStart").value, sameSite)
            Cookies.set("configEnd", document.getElementById("configEnd").value, sameSite)
            Cookies.set("slotSize", document.getElementById("slotSize").value, sameSite)
            Cookies.set("expandRows", document.getElementById("expandRows").checked, sameSite)
            const els = document.getElementsByClassName("configWeek")
            let saveDays = []
            Array.from(els).forEach((el, index) => {
                if (!el.classList.contains("btn-danger")) return
                saveDays.push(index)
            })
            Cookies.set("configDays", saveDays, sameSite)

            // Refresh
            location.reload()
        })

        // Load days to hide values
        const els = document.getElementsByClassName("configWeek")
        Array.from(els).forEach((el, index) => {
            if (!hiddenDays.includes(index)) return
            el.classList.toggle("btn-primary")
            el.classList.toggle("btn-danger")
        })

    }

}

export default UserConfig