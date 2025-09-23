import Calendar from "./calendar"

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