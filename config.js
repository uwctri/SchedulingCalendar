$(document).ready(() => {

    const prefix = "scheduling_calendar";

    $("#external-modules-configure-modal").on("show.bs.modal", (event) => {

        // Making sure we are overriding this modules"s modal only.
        let el = event.target
        console.log($(el).data("module"))
        if ($(el).data("module") !== prefix) return

        if (typeof ExternalModules.Settings.prototype.resetConfigInstancesOld === "undefined")
            ExternalModules.Settings.prototype.resetConfigInstancesOld = ExternalModules.Settings.prototype.resetConfigInstances

        ExternalModules.Settings.prototype.resetConfigInstances = () => {
            ExternalModules.Settings.prototype.resetConfigInstancesOld()
            $(".modal thead").remove()

            // Trim off some junk spans (and colons) from lists and headers
            for (const name of ["calendar-admin", "unschedulable"]) {
                $(`tr[field=${name}-list] span`).remove()
                $(`tr[field=${name}] span`).each((_, el) => $(el).text(`${$(el).text().split(".")[1]}. `))
            }
        }
    })
})