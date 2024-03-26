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
const placeholder = "Search or Filter by Provider, Subject, Location, or Event"
const choicesSelector = ".choices__inner .choices__list"

class SearchBar {

    static choices = null;
    static ready = false;

    static async build() {

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
        let providers = await API.providers()
        addCustomProperty(providers, "type", "provider")
        let subjects = await API.subjects()
        addCustomProperty(subjects, "type", "subject")
        let locations = await API.locations()
        addCustomProperty(locations, "type", "location")

        addCustomProperty(testEvents, "type", "event")

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
                        label: "Events",
                        choices: testEvents
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

        // Watch for changes in dropdown and remove placeholder text
        const choicesEl = document.querySelector(choicesSelector)
        new MutationObserver((mutations) => {
            const count = choicesEl.childElementCount
            const text = count > 0 ? "" : placeholder
            const el = document.querySelector(`.${centerClassName} input`)
            el.placeholder = text
            el.style.width = `${text.length}ch`
        }).observe(choicesEl, {
            childList: true
        })

        SearchBar.ready = true
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
        if (!SearchBar.ready) return
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
        return SearchBar.ready
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

    static getPicked(valueOnly = false, filterType = null) {
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