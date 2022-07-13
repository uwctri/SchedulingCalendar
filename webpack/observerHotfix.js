// Currently only fixing one icon issue that can't be easily
// resolved the correct way

let calendarObs = new MutationObserver((mutations) => {
    let broken = document.querySelector("span.fc-icon-fa-gear")
    if (!broken) return
    if (!broken.parentNode.querySelector(".fa-gear")) {
        let el = document.createElement("i")
        el.classList = "fa-solid fa-gear"
        broken.parentNode.replaceChild(el, broken)
    }
    broken.remove()
})

calendarObs.observe(document.getElementById("calendar"), {
    attributes: true,
    childList: true,
    subtree: true,
    characterData: true
})