import RedCap from "./redcap"
import API from "./api"
import Page, { makeAPIUrl } from "./page"
class ICS {

    static copyLink() {
        const url = `${makeAPIUrl("ics")}&pid=${Page.pid}&hash=${RedCap.user.icsHash}`
        navigator.clipboard.writeText(url)
    }

    static export() {

        const toggleICS = () => {
            const el = $.querySelector(".fc-ics-button")
            el.disabled = !el.disabled
        }

        const downloadString = (filename, text) => {
            let el = $.createElement('a')
            el.setAttribute('href', 'data:text/plaincharset=utf-8,' + encodeURIComponent(text))
            el.setAttribute('download', filename)
            el.style.display = 'none'
            $.body.appendChild(el)
            el.click()
            $.body.removeChild(el)
        }

        toggleICS()
        setTimeout(toggleICS, 10 * 1000)

        const cal = RedCap.tt("ics_cal")
        API.post({
            utility: "ics"
        }).then(result => {
            downloadString(`${RedCap.project_name} ${cal}.ics`, result.data)
        })
    }
}

export default ICS