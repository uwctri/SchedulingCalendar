// Resolve issues related to redcap and Fullcalendar clashing 
// when FC loads Font Awesome

import Calendar from "./calendar"
const head = ".fc-header-toolbar"
const foot = ".fc-footer-toolbar"

new MutationObserver((mutations) => {
    $.querySelectorAll(`${head} span.fc-icon, ${foot} span.fc-icon`).forEach((el, index) => {
        // Skip page back and page forward arrows
        if ([0, 1].includes(index))
            return

        let className = el.classList[1].replace("fc-icon-", "")
        $.querySelector(`${head} .${className}, ${foot} .${className}`)?.remove()
        $.querySelectorAll(`${head} [class*=${className}-], ${foot} [class*=${className}-]`).forEach(el => el.remove())
        let newEl = $.createElement("i")

        // Make sure the toggle icon doesn't get reset
        if (className == "fa-eye" && !Calendar._showAvailability)
            className = "fa-eye-slash"

        newEl.classList = `fa-solid ${className}`
        newEl.replaceWith(el)
        el.parentNode.replaceChild(newEl, el)
        el.remove()
    })
}).observe($.getElementById("calendar"), {
    attributes: true,
    childList: true,
    subtree: true,
    characterData: true
})