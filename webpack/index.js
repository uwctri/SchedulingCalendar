
import SearchBar from "./searchBar"
import Calendar from "./calendar"
import Page from "./page"
import "./iconObserver"
import "./style.less"

document.getElementsByClassName(`type-${Page.type}`)[0].classList.add('active')
Calendar.init()
document.getElementById("content").classList.remove("d-none")
document.getElementById("pageMenu").classList.remove("d-none")
Calendar.render()
SearchBar.build()