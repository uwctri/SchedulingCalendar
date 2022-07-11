
let calendarObs = new MutationObserver((mutations) => {
    let brokenIcon = document.querySelector("span.fc-icon-fa-gear")
    if (brokenIcon) {
        let el = document.createElement("i")
        el.classList = "fa-solid fa-gear"
        brokenIcon.parentNode.replaceChild(el, brokenIcon)
    }
})

calendarObs.observe(document.getElementById("calendar"), {
    attributes: true,
    childList: true,
    subtree: true,
    characterData: true
})