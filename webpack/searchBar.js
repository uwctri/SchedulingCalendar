import Choices from "choices.js"
import API from "./api"
import UserConfig from "./userConfig"
import Calendar from './calendar';
import Page from "./page"

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
            if (event.key != "Enter" || SearchBar.isVisible() || !SearchBar.isReady())
                return;
            document.getElementsByClassName("fc-search-button")[0].click()
            SearchBar.focus()
        }

        const changeEvent = (event) => {
            Calendar.refresh()
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
                        choices: SearchBar.filterLocations(locations)
                    },
                    {
                        label: "Visits (Events)",
                        choices: SearchBar.formatCustomProps(visits)
                    },
                    {
                        label: "Providers",
                        choices: SearchBar.filterProviders(providers)
                    },
                    {
                        label: "Subjects",
                        choices: SearchBar.filterSubjects(subjects)
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

    static filterLocations(locations) {
        let locs = []
        for (const id in locations) {
            let data = locations[id]
            // TODO for the edit Avail cal we should show all active locs that the user has access to
            // TODO for the my calednar only show ... something
            if (data.active && (!data.projects || !data.projects.length || data.projects.includes(project_code))) {
                locs.push({
                    value: id,
                    label: data["name"],
                    customProperties: {
                        ...data
                    }
                })
            }
            if (data.sites) {
                locs = locs.concat(SearchBar.filterLocations(data.sites))
            }
        }
        return locs
    }

    static filterProviders(providers) {
        providers = SearchBar.formatCustomProps(providers)
        const allProviders = !UserConfig.get().limitAvailability && (Page.type == 'edit')
        providers = providers.filter((provider) => provider.customProperties.is_local || allProviders)
        return providers
    }

    static filterSubjects(subjects) {
        subjects = SearchBar.formatCustomProps(subjects)
        subjects = subjects.filter((subject) => !subject.customProperties.is_withdrawn)
        return subjects
    }

    static formatCustomProps(raw) {
        let result = []
        console.log(raw)
        for (const id in raw) {
            const data = raw[id]
            result.push({
                value: data.value,
                label: data.label,
                customProperties: {
                    ...data
                }
            })
        }
        return result
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