// Resolve issues related to redcap and Fullcalendar clashing 
// when FC loads Font Awesome

new MutationObserver((mutations) => {
    let brokenGear = document.querySelector("span.fc-icon-fa-gear")
    let brokenSearch = document.querySelector("span.fc-icon-fa-magnifying-glass")
    if (!brokenGear && !brokenSearch) return
    if (!brokenGear.parentNode.querySelector(".fa-gear")) {
        let el = document.createElement("i")
        el.classList = "fa-solid fa-gear"
        brokenGear.parentNode.replaceChild(el, brokenGear)
    }
    if (!brokenSearch.parentNode.querySelector(".fa-magnifying-glass")) {
        let el = document.createElement("i")
        el.classList = "fa-solid fa-magnifying-glass"
        brokenSearch.parentNode.replaceChild(el, brokenSearch)
    }
    brokenSearch.remove()
    brokenGear.remove()
}).observe(document.getElementById("calendar"), {
    attributes: true,
    childList: true,
    subtree: true,
    characterData: true
})