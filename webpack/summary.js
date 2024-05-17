import SearchBar from "./searchBar"
import API from "./api"
import { DateTime } from "luxon"
import Calendar from "./calendar"

class Summary {

    static _current = null
    static _init = false

    static init() {
        Summary._init = true
        $.addEventListener("click", e => {
            const classList = e.target.classList
            if (!classList.contains("eventDate")) return
            const date = e.target.getAttribute("data-date")
            if (!date) return
            Calendar.gotoDate(date)
        })
    }

    static update() {
        // TODO when a schedule occurs we need to update this info, but for that we need to re-pull subject data
        if (!Summary._init)
            Summary.init()
        const subjects = SearchBar.getPickedSubjects(true)
        if (subjects.length != 1)
            Summary.close()
        const subject = subjects[0]
        if (subject == Summary._current)
            return
        const template = $.getElementById("eventTemplate")
        const subjectData = API.cache.subjects.data[subject]
        const nameEl = $.getElementById("subjectName")
        nameEl.innerText = subjectData.name
        for (const field in subjectData.summary_fields) {
            const sf = subjectData.summary_fields[field]
            const div = $.createElement("div")
            div.classList.add("subjectExtraInfo")
            div.innerText = `${sf.label}: ${sf.value.trim()}`
            nameEl.after(div)
        }
        API.visits().then(vData => {
            for (const v in vData) {
                const vConfig = subjectData.visits[v]
                if (!vConfig.branching_logic)
                    continue
                let clone = template.cloneNode(true)
                clone.id = ""
                clone.classList.add("cardEvent")
                const nameEl = clone.getElementsByClassName("eventName")[0]
                const dateEl = clone.getElementsByClassName("eventDate")[0]
                nameEl.innerText = vData[v].label
                if (vConfig.scheduled) {
                    const dt = DateTime.fromSQL(vConfig.scheduled[0])
                    dateEl.innerText = dt.toFormat("ccc, d LLL yyyy @ hh:mma")
                    dateEl.setAttribute("data-date", dt.toFormat("yyyy-MM-dd"))
                    nameEl.classList.add("scheduledEvent")
                } else if (vConfig.range.length == 2) {
                    const start = DateTime.fromFormat("yyyy-MM-dd", vConfig.range[0].split(" ")[0])
                    const end = DateTime.fromFormat("yyyy-MM-dd", vConfig.range[1].split(" ")[0])
                    dateEl.setAttribute("data-date", start.toFormat("yyyy-MM-dd"))
                    dateEl.innerText = `${start.toFormat("MM/dd/yyyy")} - ${end.toFormat("MM/dd/yyyy")}`
                }
                if (vData[v].notes) {
                    const notes = clone.getElementsByClassName("eventNotes")[0]
                    notes.innerText = vData[v].notes
                    notes.classList.remove("d-none")
                }
                clone.classList.remove("d-none")
                template.after(clone)
            }
            $.getElementById("subjectSummary").classList.remove("d-none")
        })

    }

    static close() {
        [...$.getElementsByClassName("subjectExtraInfo")].forEach(el => el.remove());
        [...$.getElementsByClassName("cardEvent")].forEach(el => el.remove())
        $.getElementById("subjectName").innerText = ""
        $.getElementById("subjectSummary").classList.add("d-none")
    }

}

export default Summary