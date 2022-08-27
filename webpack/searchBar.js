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

    static build() {

        let centerEl = document.getElementsByClassName(SearchBar.centerClassName)[1]
        centerEl.id = "topCenterBar" // used by CSS

        // Build out the select el and insert it
        let searchBarEl = document.createElement("select")
        searchBarEl.id = SearchBar.searchID
        searchBarEl.setAttribute("multiple", "")
        centerEl.appendChild(searchBarEl)

        // Init the picker object
        let choices = new Choices(searchBarEl, {
            removeItems: true,
            removeItemButton: true,
            placeholderValue: "Search or Filter by Provider, Subject, Location, or Event",
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