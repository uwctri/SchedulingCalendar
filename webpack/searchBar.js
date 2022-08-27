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

    constructor(selector) {
        this.selector = selector
        this.bar = new Choices(selector, {
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
        this.el = document.querySelector(this.selector).parentElement.parentElement
    }

    show() {
        this.el.classList.remove("d-none")
    }

    hide() {
        this.el.classList.add("d-none")
    }

}



export default SearchBar