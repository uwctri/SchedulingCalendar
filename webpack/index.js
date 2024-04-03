
import SearchBar from "./searchBar"
import Calendar from "./calendar"
import "./iconObserver"
import "./style.less"

//document.getElementsByClassName(`type-${pageURL.type}`)[0].classList.add('active')
Calendar.init()
document.getElementById("content").classList.remove("d-none")
document.getElementById("pageMenu").classList.remove("d-none")
Calendar.render()
SearchBar.build()
setInterval(() => Calendar.refresh(), 1000 * 60 * 2)
