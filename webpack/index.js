
import SearchBar from "./searchBar"
import Calendar from "./calendar"
import Page from "./page"
import "./iconObserver"
import "./style.less"

$.getElementsByClassName(`type-${Page.type}`)[0].classList.add('active')
Calendar.init()
$.getElementById("content").classList.remove("d-none")
$.getElementById("pageMenu").classList.remove("d-none")
Calendar.render()
SearchBar.init()

window.onbeforeprint = () => {
    if (Calendar.getView() == "agenda") {
        $.getElementById("pageMenu").style.display = "none"
        $.querySelectorAll(".fc-toolbar > div:not(#topCenterBar)").forEach(el => el.style.opacity = "0%")
        $.querySelectorAll("#calendar div, #calendar td").forEach(el => el.style.border = "none")
    }
}

window.onafterprint = () => {
    window.location.reload()
}