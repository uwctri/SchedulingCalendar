import Choices from "choices.js"

const testLocations = [
    {
        value: "loca",
        label: 'Location A',
        customProperties: {
            description: 'Custom description about child six',
            random: 'Another random custom property',
        }
    },
    {
        value: "locb",
        label: 'Location B',
        customProperties: {
            description: 'Custom description about child six',
            random: 'Another random custom property',
        }
    },
]

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

const testProviders = [
    {
        value: "prova",
        label: 'Provider A',
        customProperties: {
            description: 'Custom description about child six',
            random: 'Another random custom property',
        }
    },
    {
        value: "provb",
        label: 'Provider B',
        customProperties: {
            description: 'Custom description about child six',
            random: 'Another random custom property',
        }
    }
]

const testSubjects = [
    {
        value: "subjecta",
        label: 'Subject A',
        customProperties: {
            description: 'Custom description about child six',
            random: 'Another random custom property',
        }
    },
    {
        value: "subjectb",
        label: 'Subject B',
        customProperties: {
            description: 'Custom description about child six',
            random: 'Another random custom property',
        }
    }
]

class SearchBar {

    static centerClassName = "fc-toolbar-chunk"
    static titleClassName = "fc-toolbar-title"
    static searchID = "search-bar"
    static placeholder = "Search or Filter by Provider, Subject, Location, or Event"
    static choicesSelector = ".choices__inner .choices__list"

    static build() {

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
        centerEl.appendChild(searchBarEl)

        // Init the picker object
        let choices = new Choices(searchBarEl, {
            removeItems: true,
            removeItemButton: true,
            placeholderValue: SearchBar.placeholder,
            choices:
                [
                    {
                        label: "Locations",
                        choices: testLocations
                    },
                    {
                        label: "Events",
                        choices: testEvents
                    },
                    {
                        label: "Providers",
                        choices: testProviders
                    },
                    {
                        label: "Subjects",
                        choices: testSubjects
                    }
                ]
        })

        SearchBar.hide()

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

}



export default SearchBar