
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
SearchBar.build()