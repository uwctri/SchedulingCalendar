import SearchBar from "./searchBar"
import API from "./api"

class Summary {

    static _current = null

    static update() {
        const subjects = SearchBar.getPickedSubjects(true)
        if (subjects.length != 1)
            Summary.close()
        const subject = subjects[0]
        if (subject == Summary._current)
            return
        const template = $.getElementById("eventTemplate")
        const subjectData = API.cache.subjects.data[subject]
        // TODO add in configurable static info
        $.getElementById("subjectName").innerText = subjectData.name
        API.visits().then(vData => {
            for (const v in vData) {
                let clone = template.cloneNode(true)
                clone.id = ""
                clone.classList.add("cardEvent")
                clone.getElementsByClassName("eventName")[0].innerText = vData[v].label
                if (vData[v].notes) {
                    const notes = clone.getElementsByClassName("eventNotes")[0]
                    notes.innerText = vData[v].notes
                    notes.classList.remove("d-none")
                }
                clone.classList.remove("d-none")
                template.after(clone)
            }
            // TODO need dates / not scheduled (make clickable)
            // TODO need branching logic
            // TODO dot
            $.getElementById("subjectSummary").classList.remove("d-none")
        })

    }

    static close() {
        [...$.getElementsByClassName("cardEvent")].forEach(el => el.remove())
        $.getElementById("subjectName").innerText = ""
        $.getElementById("subjectSummary").classList.add("d-none")
        // TODO remove configurable static info
    }

}

export default Summary