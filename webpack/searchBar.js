import Choices from "choices.js"
import API from "./api"
import UserConfig from "./userConfig"
import Calendar from './calendar'
import Page from "./page"
import RedCap from "./redcap"
import Summary from "./summary"

const centerClassName = "fc-toolbar-chunk"
const titleClassName = "fc-toolbar-title"
const searchID = "search-bar"
const placeholder = RedCap.tt("search_placeholder")
const filterText = RedCap.tt("search_filter")
const choicesSelector = ".choices__inner .choices__list"
const subtitleClass = "toolbar-subtitle"
const choicesSubtitleClass = "choices-subtitle"

// TODO Visit filter doesn't seem to be working?
class SearchBar {

    static _choices = null
    static ready = false

    static async init() {

        const userConfig = UserConfig.get()

        const filterLocations = (locations) => {
            return Object.values(locations).filter(loc => loc.customProperties.active)
        }

        const filterVisits = (visits) => {
            return formatCustomProps(visits)
        }

        const filterProviders = (providers) => {
            providers = formatCustomProps(providers)
            const allProviders = !userConfig.limitAvailability && (Page.type == 'edit')
            providers = providers.filter((provider) => provider.customProperties.is_local || allProviders)
            return providers
        }

        const filterSubjects = (subjects) => {
            subjects = formatCustomProps(subjects)
            subjects = subjects.filter((subject) => !subject.customProperties.is_withdrawn)
            return subjects
        }

        const formatCustomProps = (raw) => {
            let result = []
            for (const id in raw) {
                const data = raw[id]
                result.push({
                    value: String(data.value),
                    label: data.label,
                    customProperties: {
                        ...data
                    }
                })
            }
            return result
        }

        const keyEvent = (event) => {
            if (event.key != "Enter" || SearchBar.isVisible() || !SearchBar.ready)
                return
            $.getElementsByClassName("fc-search-button")[0].click()
            $.querySelector(`.${centerClassName} input`).focus()
        }

        const changeEvent = (event) => {
            Calendar.refresh()
            const count = $.querySelector(choicesSelector).childElementCount
            const text = count > 0 ? "" : placeholder
            const el = $.querySelector(`.${centerClassName} input`)
            el.placeholder = text
            el.style.width = `${text.length}ch`
            updateFilterText()
            Summary.open()
        }

        const addProperty = (data, key, value) => {
            for (const id in data) {
                data[id][key] = value
            }
        }

        const flattenLocations = (locations, parent = null) => {
            let flatLocs = {}
            for (const id in locations) {
                const data = locations[id]
                if (data.sub) {
                    flatLocs = Object.assign(flatLocs, flattenLocations(data.sub, id))
                    delete data.sub
                }
                flatLocs[id] = {
                    value: id,
                    label: data["name"],
                    customProperties: {
                        ...data,
                        type: "location",
                        parent: parent
                    }
                }
            }
            return flatLocs
        }

        const updateFilterText = () => {
            let picks = SearchBar.getPicked()
            picks = picks.map(x => x.label)
            const el = $.getElementByClassName(subtitleClass)
            el.innerHTML = picks.length > 0 ? `${filterText}: ${picks.join(", ")}` : ""
        }

        let centerEl = $.getElementsByClassName(centerClassName)[1]
        centerEl.id = "topCenterBar" // used by CSS

        // Insert the subtitle
        let div = document.createElement('div');
        div.classList.add(subtitleClass);
        $.getElementByClassName(titleClassName).appendChild(div)

        // Build out the select el and insert it
        let searchBarEl = $.createElement("select")
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
        })
        locations = flattenLocations(locations)
        if (Page.type != "my")
            addProperty(providers, "type", "provider")
        addProperty(subjects, "type", "subject")
        addProperty(visits, "type", "visit")

        let choices = [
            {
                label: "Locations",
                choices: filterLocations(locations)
            },

            {
                label: "Providers",
                choices: filterProviders(providers)
            },
        ]

        if (Page.type != "edit") {
            choices = choices.concat([
                {
                    label: "Subjects",
                    choices: filterSubjects(subjects)
                },
                {
                    label: "Visits (Events)",
                    choices: filterVisits(visits)
                }
            ])
        }

        // Init the picker object
        SearchBar._choices = new Choices(searchBarEl, {
            allowHTML: false,
            removeItems: true,
            removeItemButton: true,
            placeholderValue: placeholder,
            choices: choices,
            callbackOnCreateTemplates: (template) => {
                // Add in the record_id for all users and all coded values for admins
                return {
                    choice: (config, data, hoverText) => {
                        const classNames = config.classNames
                        const subtitle = RedCap.user.isCalendarAdmin || (data.customProperties.type == "subject") ? `<span class="${choicesSubtitleClass}">${data.value}</span>` : ""
                        return template(`
                          <div class="${classNames.item} ${classNames.itemChoice} ${data.disabled ? classNames.itemDisabled : classNames.itemSelectable}" 
                          data-select-text="${config.itemSelectText}" data-choice ${data.disabled ? 'data-choice-disabled aria-disabled="true"' : 'data-choice-selectable'} 
                          data-id="${data.id}" data-value="${data.value}" ${data.groupId > 0 ? 'role="treeitem"' : 'role="option"'}>
                            ${data.label} ${subtitle}
                          </div>
                        `);
                    },
                }
            },
        })

        SearchBar.hide()
        searchBarEl.style.display = ""
        searchBarEl.addEventListener('change', changeEvent)
        $.addEventListener("keyup", keyEvent)
        SearchBar.ready = true

        if (Page.id || Page.record) {
            SearchBar._choices.setChoiceByValue(Page.id || Page.record)
            Summary.open()
            changeEvent()
        }

        if (Page.type == "edit" && userConfig.filterToSelf) {
            // If the user is not listed then the search bar just skips it
            SearchBar._choices.setChoiceByValue(RedCap.user.username)
            changeEvent()
        }
    }

    static show() {
        $.getElementById(searchID).parentElement.parentElement.classList.remove("d-none")
        $.getElementsByClassName(titleClassName)[0].classList.add("d-none")
    }

    static hide() {
        $.getElementById(searchID).parentElement.parentElement.classList.add("d-none")
        $.getElementsByClassName(titleClassName)[0].classList.remove("d-none")
    }

    static toggle() {
        if (!SearchBar.ready) return
        SearchBar.isVisible() ? SearchBar.hide() : SearchBar.show()
    }

    static isVisible() {
        return $.getElementsByClassName(titleClassName)[0].classList.contains("d-none")
    }

    static getPicked(valueOnly = false, filterType = null) {
        if (SearchBar._choices == null)
            return []
        let picked = SearchBar._choices.getValue()
        if (filterType)
            picked = picked.filter(item => item.customProperties.type == filterType)
        if (valueOnly)
            picked = picked.map(x => x.value)
        return picked
    }

    static getPickedProviders(valueOnly = false) {
        let picked = SearchBar.getPicked(valueOnly, "provider")
        if (Page.type == "my")
            picked.push(RedCap.user.username)
        return picked
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