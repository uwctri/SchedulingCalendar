import Choices from "choices.js"
import API from "./api"

const testEvents = [
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

class SearchBar {

    static centerClassName = "fc-toolbar-chunk"
    static titleClassName = "fc-toolbar-title"
    static searchID = "search-bar"
    static placeholder = "Search or Filter by Provider, Subject, Location, or Event"
    static choicesSelector = ".choices__inner .choices__list"

    static async build() {

        let centerEl = document.getElementsByClassName(SearchBar.centerClassName)[1]
        centerEl.id = "topCenterBar" // used by CSS

        // Add 50px to search bar on edit page
        if (Object.fromEntries(new URLSearchParams(location.search))["type"] == "edit") {
            document.getElementById("content").style.setProperty("--calendar-searchbar-width", "50px");
        }

        // Build out the select el and insert it
        let searchBarEl = document.createElement("select")
        searchBarEl.id = SearchBar.searchID
        searchBarEl.setAttribute("multiple", "")
        searchBarEl.style.display = "none"
        centerEl.appendChild(searchBarEl)

        // Fetch data for the dropdown
        const providers = await API.providers()
        const subjects = await API.subjects()
        const locations = await API.locations()
        console.log(SearchBar.formatLocations(locations))

        // Init the picker object
        let choices = new Choices(searchBarEl, {
            removeItems: true,
            removeItemButton: true,
            placeholderValue: SearchBar.placeholder,
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
        const choicesEl = document.querySelector(SearchBar.choicesSelector)
        new MutationObserver((mutations) => {
            const count = choicesEl.childElementCount
            const text = count > 0 ? "" : SearchBar.placeholder
            const el = document.querySelector(`.${SearchBar.centerClassName} input`)
            el.placeholder = text
            el.style.width = `${text.length}ch`
        }).observe(choicesEl, {
            childList: true
        })

    }

    static show() {
        document.getElementById(SearchBar.searchID).parentElement.parentElement.classList.remove("d-none")
        document.getElementsByClassName(SearchBar.titleClassName)[0].classList.add("d-none")
    }

    static hide() {
        document.getElementById(SearchBar.searchID).parentElement.parentElement.classList.add("d-none")
        document.getElementsByClassName(SearchBar.titleClassName)[0].classList.remove("d-none")
    }

    static toggle() {
        if (SearchBar.isVisible()) {
            SearchBar.hide()
        } else {
            SearchBar.show()
        }
    }

    static isVisible() {
        return document.getElementsByClassName(SearchBar.titleClassName)[0].classList.contains("d-none")
    }

    static focus() {
        document.querySelector(`.${SearchBar.centerClassName} input`).focus()
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
                    label: data["name"]
                })
            }
            if (data.sites) {
                locs = locs.concat(SearchBar.formatLocations(data.sites))
            }
        }
        return locs
    }

}



export default SearchBar