import Cookies from 'js-cookie'
import Swal from 'sweetalert2'
import html from './html/userConfig.html'

const sameSite = { sameSite: 'strict' }
const redcapBtnColor = "#337ab7"
const defaultStart = "05:00"
const defaultEnd = "18:00"
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
            expandRows: typeof expandRows !== "boolean" ? defaultExpandRows : expandRows
        }
    },

    open: () => {

        const { start, end, hiddenDays, expandRows } = UserConfig.get()

        // Modify the html with current values
        let newHtml = html.replace("START-TIME", start).replace("END-TIME", end).replace("CHECKED", expandRows ? "checked" : "")

        Swal.fire({
            title: "User Configuration",
            html: newHtml,
            confirmButtonColor: redcapBtnColor,
            confirmButtonText: "Save",
            customClass: {
                container: 'userConfigModal'
            }
        }).then(() => {
            // Save everything back to cookies
            Cookies.set("configStart", document.getElementById("configStart").value, sameSite)
            Cookies.set("configEnd", document.getElementById("configEnd").value, sameSite)
            Cookies.set("expandRows", document.getElementById("expandRows").value == "1", sameSite)
            const els = document.getElementsByClassName("configWeek")
            let saveDays = []
            Array.from(els).forEach((el, index) => {
                if (!el.classList.contains("btn-danger")) return
                saveDays.push(index)
            })
            Cookies.set("configDays", saveDays, sameSite)
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