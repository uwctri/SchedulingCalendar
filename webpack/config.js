// This file must use jQuery as the 'show.bs.modal' event
// isn't available vai the vanilla JS listener 

import "./style.less"
import Adapter from "./adapter"

const modalName = "Scheduling & Availability"

document.addEventListener("DOMContentLoaded", () => {

    let modal = document.getElementById("external-modules-configure-modal")

    Adapter.listenShowModal(modal, (event) => {

        if (document.querySelectorAll(".modal .module-name")[1].textContent != modalName)
            return
        modal.classList.add("calConfig")

        if (typeof ExternalModules.Settings.prototype.resetConfigInstancesOld === "undefined")
            ExternalModules.Settings.prototype.resetConfigInstancesOld = ExternalModules.Settings.prototype.resetConfigInstances

        ExternalModules.Settings.prototype.resetConfigInstances = () => {
            ExternalModules.Settings.prototype.resetConfigInstancesOld()
            document.querySelectorAll("tr[field=visit-branch-logic-value] .external-modules-input-td").forEach((el) => {
                el.innerHTML = el.innerHTML[0] == "=" ? el.innerHTML : `= ${el.innerHTML}`
            })
        }
    })
})