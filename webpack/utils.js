import API from "./api"
import UserConfig from "./userConfig"
import RedCap from "./redcap"
import SearchBar from "./searchBar"

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
    })
}

export const buildLocationDropdown = (el, stillOpenFn) => {
    // TODO Some locations should be filtered out
    API.locations().then(locationsData => {
        if (!stillOpenFn()) return
        const select = $.getElementById(el)
        const loopOver = (obj) => {
            for (const code in obj) {
                if (obj[code].sub)
                    loopOver(obj[code].sub)
                if (!obj[code].active)
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

export const buildProviderDropdown = (el, stillOpenFn) => {
    // TODO only list the providers that are available?
    const select = $.getElementById(el)
    if (!RedCap.user.isCalendarAdmin) {
        let option = $.createElement("option")
        option.value = RedCap.user.username
        option.text = RedCap.user.name
        option.selected = true
        select.add(option)
    } else {
        API.providers().then(providers => {
            if (!stillOpenFn()) return
            for (const k in providers) {
                if (providers[k].is_unschedulable || !providers[k].is_local)
                    continue
                let option = $.createElement("option")
                option.value = providers[k].value
                option.text = providers[k].label
                select.add(option)
            }
        })
    }
}

export const buildVisitDropdown = (el, subject, defaultSelection, stillOpenFn) => {
    const subjectData = subject ? API.cache.subjects.data[subject] : null
    API.visits().then(visitData => {
        if (!stillOpenFn()) return
        const select = $.getElementById(el)
        for (const k in visitData) {
            if (subject && subjectData && subjectData["visits"].includes(visitData[k].value))
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
            $.getElementById("aPopSubject").value = selSub[0].customProperties.record_id
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