import PopOver from "./popover"
import { savingAnimation } from "./utils"
import html from './html/cleanUp.html'
import { DateTime } from 'luxon'
import Calendar from './calendar'
import Swal from 'sweetalert2'
import API from "./api"

class CleanUp {

    static open() {
        PopOver.close()
        Swal.fire({
            title: "Admin Tools",
            html: html,
            confirmButtonText: "Clean Up!",
            didOpen: CleanUp.initModal,
            preConfirm: () => {
                const btnEl = "swal2-confirm"

                // TODO
                // Check if box was checked
                // Grab end date

                API.deleteAvailability({
                    "purge": true,
                    "end": DateTime.now().toISO()
                }).then(data => {
                    Calendar.refresh()
                })

                savingAnimation(btnEl)
                setTimeout(Swal.close, 2000)
                return false
            },
        })
    }

    static initModal() {

    }
}

export default CleanUp