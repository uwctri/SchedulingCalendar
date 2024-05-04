import SearchBar from "./searchBar"

class Summary {

    static _current = null

    static update() {
        const subjects = SearchBar.getPickedSubjects(true)
        if (subjects.length != 1)
            Summary.close()
        const subject = subjects[0]
        if (subject == Summary._current)
            return
        // TODO build the box
        $.getElementById("subjectSummary").classList.remove("d-none")
    }

    static close() {
        // TODO hide and clear info in the summary box
        $.getElementById("subjectSummary").classList.add("d-none")
    }

}

export default Summary