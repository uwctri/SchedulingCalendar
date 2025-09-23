
import SearchBar from "./searchBar"
import Calendar from "./calendar"
import Page from "./page"
import RedCap from "./redcap"
import "./printing"
import "./iconObserver"
import "./style.less"

$.getElementsByClassName(`type-${Page.type}`)[0].classList.add('active')
Calendar.init()
$.getElementById("content").classList.remove("d-none")
$.getElementById("pageMenu").classList.remove("d-none")
Calendar.render()
SearchBar.init()

// Setup Timezone Picker
if (RedCap.timezones.length > 1) {
    const el = $.getElementById("timezonePicker")
    el.style.display = "inline-block"
    const tzPicker = el.getElementsByTagName("select")[0]
    RedCap.timezones.forEach(tz => {
        const option = document.createElement("option")
        option.value = tz
        option.textContent = tz
        tzPicker.appendChild(option)
    })
    $.getElementByClassName("fc-header-toolbar").style.marginBottom = "2em"
}