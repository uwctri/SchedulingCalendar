import PopOver from "./popover"
import { savingAnimation } from "./utils"
import template from './html/cleanUp.html'
import { DateTime } from 'luxon'
import Calendar from './calendar'
import Swal from 'sweetalert2'
import RedCap from "./redcap"
import API from "./api"

const html = RedCap.ttHTML(template)
class CleanUp {

    static open() {
        PopOver.close()
        Swal.fire({
            title: RedCap.tt("admin_title"),
            html: html,
            confirmButtonText: RedCap.tt("admin_clean"),
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
                    const codes = Object.keys(API.cache.availabilityCodes.data)
                    delAvail = API.deleteAvailability({
                        "providers": "*",
                        "locations": "*",
                        "group": codes,
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