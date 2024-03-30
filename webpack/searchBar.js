import Choices from "choices.js"
import API from "./api"

let testEvents = [
    {
        value: "eventa",
        label: 'Event A',
        customProperties: {
            description: 'Custom description about child six',
            random: 'Another random custom property',
        }
    },
    {
        value: "eventb",
        label: 'Event B',
        customProperties: {
            description: 'Custom description about child six',
            random: 'Another random custom property',
        }
    },
]

const centerClassName = "fc-toolbar-chunk"
const titleClassName = "fc-toolbar-title"
const searchID = "search-bar"
const placeholder = "Search or Filter by Provider, Subject, Location, or Visit"
const choicesSelector = ".choices__inner .choices__list"

class SearchBar {

    static choices = null;
    static _ready = false;

    static async build() {

        const keyEvent = (event) => {
            if (event.key != "s" || SearchBar.isVisible() || !SearchBar.isReady())
                return;
            document.getElementsByClassName("fc-search-button")[0].click()
            SearchBar.focus()
        }

        const changeEvent = (event) => {
            calendar.refetchEvents()
            const count = document.querySelector(choicesSelector).childElementCount
            const text = count > 0 ? "" : placeholder
            const el = document.querySelector(`.${centerClassName} input`)
            el.placeholder = text
            el.style.width = `${text.length}ch`
        }

        const addCustomProperty = (data, key, value) => {
            for (const id in data) {
                data[id].customProperties = data[id].customProperties || {}
                data[id].customProperties[key] = value
            }
        }

        let centerEl = document.getElementsByClassName(centerClassName)[1]
        centerEl.id = "topCenterBar" // used by CSS

        // Build out the select el and insert it
        let searchBarEl = document.createElement("select")
        searchBarEl.id = searchID
        searchBarEl.setAttribute("multiple", "")
        searchBarEl.style.display = "none"
        centerEl.appendChild(searchBarEl)

        // Fetch data for the dropdown
        let providers, subjects, locations, visits
        await Promise.all([API.providers(), API.subjects(), API.locations(), API.visits()]).then((values) => {
            providers = values[0]
            subjects = values[1]
            locations = values[2]
            visits = values[3]
        });
        addCustomProperty(providers, "type", "provider")
        addCustomProperty(subjects, "type", "subject")
        addCustomProperty(locations, "type", "location")
        addCustomProperty(visits, "type", "visit")

        // Init the picker object
        SearchBar.choices = new Choices(searchBarEl, {
            allowHTML: false,
            removeItems: true,
            removeItemButton: true,
            placeholderValue: placeholder,
            choices:
                [
                    {
                        label: "Locations",
                        choices: SearchBar.formatLocations(locations)
                    },
                    {
                        label: "Visits (Events)",
                        choices: SearchBar.formatVists(visits)
                    },
                    {
                        label: "Providers",
                        choices: Object.values(providers)
                    },
                    {
                        label: "Subjects",
                        choices: Object.values(subjects)
                    }
                ]
        })

        SearchBar.hide()
        searchBarEl.style.display = ""
        searchBarEl.addEventListener('change', changeEvent)
        document.addEventListener("keyup", keyEvent)
        SearchBar._ready = true
    }

    static show() {
        document.getElementById(searchID).parentElement.parentElement.classList.remove("d-none")
        document.getElementsByClassName(titleClassName)[0].classList.add("d-none")
    }

    static hide() {
        document.getElementById(searchID).parentElement.parentElement.classList.add("d-none")
        document.getElementsByClassName(titleClassName)[0].classList.remove("d-none")
    }

    static toggle() {
        if (!SearchBar._ready) return
        console.log(SearchBar.getPicked())
        if (SearchBar.isVisible()) {
            SearchBar.hide()
        } else {
            SearchBar.show()
        }
    }

    static isVisible() {
        return document.getElementsByClassName(titleClassName)[0].classList.contains("d-none")
    }

    static isReady() {
        return SearchBar._ready
    }

    static focus() {
        document.querySelector(`.${centerClassName} input`).focus()
    }

    static formatLocations(rawLocations) {
        let locs = []
        for (const id in rawLocations) {
            let data = rawLocations[id]
            // TODO for the edit Avail cal we should show all active locs that the user has access to
            // TODO for the my calednar only show ... something
            if (data.active && (!data.projects || !data.projects.length || data.projects.includes(project_code))) {
                locs.push({
                    value: id,
                    label: data["name"],
                    ...data
                })
            }
            if (data.sites) {
                locs = locs.concat(SearchBar.formatLocations(data.sites))
            }
        }
        return locs
    }

    static formatVists(rawVisits) {
        let visits = []
        for (const id in rawVisits) {
            let data = rawVisits[id]
            visits.push({
                value: data["code"],
                label: data["label"],
                customProperties: {
                    ...data
                }
            })
        }
        return visits
    }

    static getPicked(valueOnly = false, filterType = null) {
        if (SearchBar.choices == null) return []
        let picked = SearchBar.choices.getValue()
        if (filterType) {
            picked = picked.filter(item => item.customProperties.type == filterType)
        }
        if (valueOnly) {
            picked = picked.map(x => x.value)
        }
        return picked
    }

    static getPickedProviders(valueOnly = false) {
        return SearchBar.getPicked(valueOnly, "provider")
    }

    static getPickedSubjects(valueOnly = false) {
        return SearchBar.getPicked(valueOnly, "subject")
    }

    static getPickedLocations(valueOnly = false) {
        return SearchBar.getPicked(valueOnly, "location")
    }

    static getPickedEvents(valueOnly = false) {
        return SearchBar.getPicked(valueOnly, "event")
    }

}

export default SearchBar