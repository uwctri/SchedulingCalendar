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
            customClass: {
                container: 'cleanupModal'
            },
            didOpen: CleanUp.initModal,
            preConfirm: () => {
                const btnEl = "swal2-confirm"
                const removeAvail = $.getElementById("removeAvailability").value
                const removeAppts = $.getElementById("withdrawnAppts").value

                let delAvail = Promise.resolve([])
                if (removeAvail) {
                    delAvail = API.deleteAvailability({
                        "providers": "*",
                        "locations": "*",
                        "group": "*",
                        "start": DateTime.now().minus({ years: 100 }).toISO(),
                        "end": DateTime.now().minus({ days: 1 }).toISO()
                    })
                }

                let delAppts = Promise.resolve([])
                if (removeAppts) {
                    const subjects = API.cache.subjects.data
                    const withdrawn = Object.keys(subjects).filter(record => subjects[record].is_withdrawn)
                    if (withdrawn.length) {
                        delAppts = API.deleteAppointments({
                            "subjects": withdrawn,
                            "start": DateTime.now().plus({ days: 1 }).toISO(),
                            "end": DateTime.now().plus({ years: 100 }).toISO()
                        })
                    }
                }

                Promise.all([delAvail, delAppts]).then(values => {
                    Calendar.refresh()
                })

                savingAnimation(btnEl)
                setTimeout(Swal.close, 4000)
                return false
            },
        })
    }
}

export default CleanUp