import SearchBar from "./searchBar"
import API from "./api"
import { DateTime } from "luxon"

class Summary {

    static _current = null

    static update() {
        // TODO when a schedule occurs we need to update this info, but for that we need to re-pull subject data
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
                if (!subjectData.visits[v].branching_logic)
                    continue
                let clone = template.cloneNode(true)
                clone.id = ""
                clone.classList.add("cardEvent")
                const nameEl = clone.getElementsByClassName("eventName")[0]
                const dateEl = clone.getElementsByClassName("eventDate")[0]
                nameEl.innerText = vData[v].label
                if (subjectData.visits[v].scheduled) {
                    let dt = DateTime.fromSQL(subjectData.visits[v].scheduled[0]).toFormat("ccc, d LLL yyyy @ hh:mma")
                    dateEl.innerText = dt
                    nameEl.classList.add("scheduledEvent")
                }
                if (vData[v].notes) {
                    const notes = clone.getElementsByClassName("eventNotes")[0]
                    notes.innerText = vData[v].notes
                    notes.classList.remove("d-none")
                }
                clone.classList.remove("d-none")
                template.after(clone)
            }
            // TODO need date range (make clickable?)
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