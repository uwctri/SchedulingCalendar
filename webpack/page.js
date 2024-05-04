let Page = Object.fromEntries(new URLSearchParams(location.search))
Page.type = Page.type || "schedule"

export const goToRecord = (record, pid) => {
    const url = makeRecordUrl(record, pid)
    window.open(url, '_blank').focus();
}

export const makeRecordUrl = (record, pid = Page.pid) => {
    return location.href.split("ExternalModules")[0] + `DataEntry/record_home.php?pid=${pid}&id=${record}`
}

export default Page