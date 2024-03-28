// This file must use jQuery as the 'show.bs.modal' event
// isn't available vai the vanilla JS listener 

import "./style.less"

jQuery(document).ready(() => {

    const prefix = "scheduling_calendar"
    let $modal = jQuery('#external-modules-configure-modal')

    $modal.on("show.bs.modal", (event) => {

        // Making sure we are overriding this modules"s modal only.
        if (jQuery(event.target).data("module") !== prefix) return
        $modal.addClass('calConfig')

        if (typeof ExternalModules.Settings.prototype.resetConfigInstancesOld === "undefined")
            ExternalModules.Settings.prototype.resetConfigInstancesOld = ExternalModules.Settings.prototype.resetConfigInstances

        ExternalModules.Settings.prototype.resetConfigInstances = () => {
            ExternalModules.Settings.prototype.resetConfigInstancesOld()

            $modal.find("tr[field=event-branch-logic-value] .external-modules-input-td").not(':contains(=)').prepend('= ')
        }
    })
})