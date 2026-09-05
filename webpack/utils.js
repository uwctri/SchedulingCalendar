import API from "./api"
import UserConfig from "./userConfig"
import SearchBar from "./searchBar"
import { DateTime } from 'luxon'
import RedCap from "./redcap"

export const buildGroupDropdown = (el, stillOpenFn) => {
    API.availabilityCodes({
        "all_availability": !UserConfig.get().limitAvailability
    }).then(groupData => {
        if (!stillOpenFn()) return
        const select = $.getElementById(el)
        for (const k in groupData) {
            let option = $.createElement("option")
            option.value = groupData[k].value
            option.text = groupData[k].label
            select.add(option)
        }
        if (Object.keys(groupData).length == 1) {
            select.value = Object.keys(groupData)[0]
            // Note: Can't hide the select, we already rendered the dropdown
        }
    })
}

export const buildLocationDropdown = (el, stillOpenFn, selectionInfo = null) => {
    let availabilityPromise = getSelectedAvailability(selectionInfo)
    let locationPromise = API.locations()
    Promise.all([availabilityPromise, locationPromise]).then(([availabilityData, locationsData]) => {
        if (!stillOpenFn()) return
        const validLocations = availabilityData.map(e => e.location)
        const select = $.getElementById(el)
        const loopOver = (obj) => {
            for (const code in obj) {
                if (obj[code].sub)
                    loopOver(obj[code].sub)
                if (!obj[code].active)
                    continue
                if (selectionInfo && !validLocations.includes(code))
                    continue
                let option = $.createElement("option")
                option.value = code
                option.text = obj[code].name
                select.add(option)
            }
        }
        loopOver(locationsData)
    })
}

export const buildProviderDropdown = (el, stillOpenFn, selectionInfo = null) => {
    let availabilityPromise = getSelectedAvailability(selectionInfo)
    let providersPromise = API.providers()
    Promise.all([availabilityPromise, providersPromise]).then(([availabilityData, providersData]) => {
        if (!stillOpenFn()) return
        const validProviders = availabilityData.map(e => e.user)
        const select = $.getElementById(el)
        for (const k in providersData) {
            if (providersData[k].is_unschedulable || !providersData[k].is_local)
                continue
            if (selectionInfo && !validProviders.includes(providersData[k].value))
                continue
            let option = $.createElement("option")
            option.value = providersData[k].value
            option.text = providersData[k].label
            select.add(option)
        }
        // If only 1 provider (and the blank) exists then select it
        if (select.options.length == 2)
            select.value = select.options[1].value
    })
}

const getSelectedAvailability = (selectionInfo) => {
    return selectionInfo ? API.getAvailability({
        start: DateTime.fromISO(selectionInfo.startStr).toFormat("yyyy-MM-dd HH:mm:ss"),
        end: DateTime.fromISO(selectionInfo.endStr).toFormat("yyyy-MM-dd HH:mm:ss"),
        providers: [],
        locations: [],
        all_availability: false,
        allow_overflow: true
    }, true) : Promise.resolve([])
}

export const buildVisitDropdown = (el, subject, defaultSelection, stillOpenFn) => {
    const subjectData = subject ? API.cache.subjects.data[subject] : null
    API.visits().then(visitData => {
        if (!stillOpenFn()) return
        const select = $.getElementById(el)
        for (const k in visitData) {
            if (subject && subjectData && (subjectData.visits[k].scheduled || !subjectData.visits[k].branching_logic))
                continue
            let option = $.createElement("option")
            option.value = visitData[k].value
            option.text = visitData[k].label
            select.add(option)
        }
        if (defaultSelection && [...select.options].map(e => e.value).includes(defaultSelection))
            select.value = defaultSelection
    })
}

export const buildSubjectDropdown = (el, stillOpenFn) => {
    API.subjects().then(subjectData => {
        if (!stillOpenFn()) return
        const select = $.getElementById(el)
        for (const k in subjectData) {
            if (subjectData[k].is_withdrawn)
                continue
            let option = $.createElement("option")
            option.value = subjectData[k].value
            option.text = subjectData[k].label
            select.add(option)
        }

        // If one subject is in the filter list then default to that subject
        const selSub = SearchBar.getPickedSubjects()
        if (selSub.length == 1) {
            const el = $.getElementById("aPopSubject")
            el.value = selSub[0].customProperties.record_id
            el.dispatchEvent(new Event('change'));
        }
    })
}

export const savingAnimation = (el) => {
    const loadingDots = `<div class="loading-dots"></div>`
    el = $.getElementsByClassName(el)[0]
    el.style.width = getComputedStyle(el).width
    el.innerHTML = loadingDots
}

export const genRowCol = (arr, width) => {
    let t = ""
    let size = 0
    while (size * width < arr.length) {
        t = t + `<div class="row g-0">`
        arr.slice(width * size, width * (size + 1)).forEach(el => {
            t = t + `<div class="col">${el}</div>`
        });
        t = t + `</div>`
        size = size + 1
    }
    return t
}

export const setProviderCurrentUser = (elID) => {
    API.providers().then(providers => {
        if (Object.keys(providers).includes(RedCap.user.username))
            $.getElementById(elID).value = RedCap.user.username
    })
} 