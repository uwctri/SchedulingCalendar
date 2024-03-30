import API from "./api"
import UserConfig from "./userConfig"

export const buildGroupDropdown = (el, stillOpenFn) => {
    API.availabilityCodes({
        "all_availability": UserConfig.get().all_availability
    }).then(groupData => {
        if (!stillOpenFn()) return
        const select = document.getElementById(el)
        for (const k in groupData) {
            let option = document.createElement("option")
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
        const select = document.getElementById(el)
        const loopOver = (obj) => {
            for (const code in obj) {
                if (obj[code].sites)
                    loopOver(obj[code].sites)
                if (!obj[code].active)
                    continue
                let option = document.createElement("option")
                option.value = code
                option.text = obj[code].name
                select.add(option)
            }
        }
        loopOver(locationsData)
    })
}

export const buildProviderDropdown = (el, stillOpenFn) => {
    const select = document.getElementById(el)
    if (!user.isCalendarAdmin) {
        let option = document.createElement("option")
        option.value = user.username
        option.text = user.name
        option.selected = true
        select.add(option)
    } else {
        API.providers().then(providers => {
            if (!stillOpenFn()) return
            for (const k in providers) {
                if (providers[k].is_unschedulable)
                    continue
                let option = document.createElement("option")
                option.value = providers[k].value
                option.text = providers[k].label
                select.add(option)
            }
        })
    }
}