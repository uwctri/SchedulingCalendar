$(document).ready(() => {

    const sotCheckBox = () => {
        $(".modal tr").show()
        const isSot = $("input[name=is-sot]").is(":checked")
        const selector = "tr[field=descriptive-data-collection]"
        let els = isSot ? $(selector).nextAll() : $("tr[field=descriptive-source-of-truth]").nextUntil(selector)
        els.addBack().hide()
    }

    $("#external-modules-configure-modal").on("show.bs.modal", (event) => {

        // Making sure we are overriding this modules"s modal only.
        let el = event.target
        if ($(el).data("module") !== "scheduling_and_availability") return

        if (typeof ExternalModules.Settings.prototype.resetConfigInstancesOld === "undefined")
            ExternalModules.Settings.prototype.resetConfigInstancesOld = ExternalModules.Settings.prototype.resetConfigInstances

        ExternalModules.Settings.prototype.resetConfigInstances = () => {
            ExternalModules.Settings.prototype.resetConfigInstancesOld()
            $(".modal thead").remove()

            // Hide some fields if we are SOT or not
            $("input[name=is-sot]").on("click", sotCheckBox)
            sotCheckBox()

            // Make json location wide
            const $jsonEl = $(".modal tr[field=locations-json]")
            if ($jsonEl.find("td").length == 3) {
                $jsonEl.find("td:first, td:last").remove()
                $jsonEl.find("td:first").attr("colspan", "3").prepend(
                    "<b> Location JSON:</b><br>Consult documentation for JSON format:"
                )
                $jsonEl.find("input").addClass("mt-1")
            }

            // Trim off some junk spans (and colons) from lists and headers
            for (const name of ["calendar-admin", "unschedulable"]) {
                $(`tr[field=${name}-list] span`).remove()
                $(`tr[field=${name}] span`).each((_, el) => $(el).text(`${$(el).text().split(".")[1]}. `))
            }

            for (const name of ["data-collection", "source-of-truth"]) {
                $(`tr[field=descriptive-${name}]`).find("td:first, td:last, span").remove()
                $(`tr[field=descriptive-${name}] td`).attr("colspan", "3")
            }
        }
    })
})