let Page = Object.fromEntries(new URLSearchParams(location.search))
Page.type = Page.type || "schedule"

export const updateUrlState = (updates = {}) => {
    const params = new URLSearchParams(window.location.search)

    for (const [key, val] of Object.entries(updates)) {
        if (val === null || val === undefined || val === "") {
            params.delete(key)
            delete Page[key]
        } else {
            params.set(key, String(val))
            Page[key] = String(val)
        }
    }

    const newQuery = params.toString()
    const newUrl = `${window.location.pathname}${newQuery ? "?" + newQuery : ""}${window.location.hash}`
    window.history.replaceState(null, "", newUrl)

    const curDate = params.get("date")
    const curView = params.get("view")
    for (const type of ["my", "schedule", "edit"]) {
        const el = document.querySelector(`.type-${type}`)
        if (!el) continue
        try {
            const u = new URL(el.href, window.location.origin)
            if (curDate) {
                u.searchParams.set("date", curDate)
            } else {
                u.searchParams.delete("date")
            }
            if (curView) {
                u.searchParams.set("view", curView)
            } else {
                u.searchParams.delete("view")
            }
            el.href = u.toString()
        } catch (e) {
            let baseUrl = el.href.split("&date=")[0].split("&view=")[0]
            if (curDate) baseUrl += `&date=${encodeURIComponent(curDate)}`
            if (curView) baseUrl += `&view=${encodeURIComponent(curView)}`
            el.href = baseUrl
        }
    }
}

export const goToRecord = (record, pid) => {
    const url = makeRecordUrl(record, pid)
    window.open(url, '_blank').focus()
}

export const makeRecordUrl = (record, pid = Page.pid) => {
    return location.href.split("ExternalModules")[0] + `DataEntry/record_home.php?pid=${pid}&id=${record}`
}

export const makeAPIUrl = (page) => {
    return location.href.split("redcap_v")[0] + `api/?NOAUTH&type=module&prefix=scheduling_calendar&page=${page}`
}

export default Page