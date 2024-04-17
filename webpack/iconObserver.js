// Resolve issues related to redcap and Fullcalendar clashing 
// when FC loads Font Awesome

import Calendar from "./calendar"
const head = ".fc-header-toolbar"

new MutationObserver((mutations) => {
    document.querySelectorAll(`${head} span.fc-icon`).forEach((el, index) => {
        // Skip page back and page forward arrows
        if ([0, 1].includes(index))
            return

        let className = el.classList[1].replace("fc-icon-", "")
        document.querySelector(`${head} .${className}`)?.remove()
        document.querySelectorAll(`${head} [class*=${className}-]`).forEach(el => el.remove())
        let newEl = document.createElement("i")

        // Make sure the toggle icon doesn't gett reset
        if (className == "fa-eye" && !Calendar._showAvailability)
            className = "fa-eye-slash"

        newEl.classList = `fa-solid ${className}`
        newEl.replaceWith(el)
        el.parentNode.replaceChild(newEl, el)
        el.remove()
    })
}).observe(document.getElementById("calendar"), {
    attributes: true,
    childList: true,
    subtree: true,
    characterData: true
})