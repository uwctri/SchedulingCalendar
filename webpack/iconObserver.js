// Resolve issues related to redcap and Fullcalendar clashing 
// when FC loads Font Awesome

new MutationObserver((mutations) => {
    Array.from(document.querySelectorAll(".fc-header-toolbar span.fc-icon")).forEach((el, index) => {
        if ([0, 1].includes(index)) // Skip page back and page forward arrows
            return
        const className = el.classList[1].replace("fc-icon-", "")
        document.querySelector(`.${className}`)?.remove()
        let newEl = document.createElement("i")
        newEl.classList = `fa-solid ${className}`
        newEl.replaceWith(el)
        el.parentNode.replaceChild(newEl, el)
        el.remove()
    });
}).observe(document.getElementById("calendar"), {
    attributes: true,
    childList: true,
    subtree: true,
    characterData: true
})