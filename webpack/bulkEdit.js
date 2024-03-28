import Swal from 'sweetalert2'
import html from './html/bulkEdit.html'
import API from "./api"
import PopOver from "./popover"

class BulkEdit {

    static open() {

        // Modify the html with current values
        const newHTML = html // TODO
        const btnColor = getComputedStyle(document.getElementById("content")).getPropertyValue("--redcap-btn-color")

        PopOver.close()
        Swal.fire({
            html: newHTML,
            confirmButtonColor: btnColor,
            confirmButtonText: "Save",
            customClass: {
                container: 'bulkEditModal'
            },
            didOpen: () => {
                BulkEdit.initPicker()
            }
        }).then((result) => {

            // Bail if save wasn't clicked
            if (!result.isConfirmed) return

            // TODO 
            //API.setAvailability()

            //calendar.refetchEvents()
        })
    }

    static initPicker() {


    }

}

export default BulkEdit