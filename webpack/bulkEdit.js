import Swal from 'sweetalert2'
import html from './html/bulkEdit.html'
import API from "./api"
import { buildGroupDropdown, buildLocationDropdown, buildProviderDropdown } from "./utils"
import Litepicker from 'litepicker';
import PopOver from "./popover"

const modalWidth = "760px"
const defaultStart = "08:00 AM"
const defaultEnd = "05:00 PM"
class BulkEdit {

    static picker = null

    static open() {

        // Modify the html with current values
        const newHTML = html // TODO
        const btnColor = getComputedStyle(document.getElementById("content")).getPropertyValue("--redcap-btn-color")

        PopOver.close()
        Swal.fire({
            title: "Bulk Availability Edit",
            html: newHTML,
            confirmButtonColor: btnColor,
            confirmButtonText: "Save",
            customClass: {
                container: 'bulkEditModal'
            },
            didOpen: BulkEdit.initModal,
            width: modalWidth,
        }).then((result) => {

            // Bail if save wasn't clicked
            if (!result.isConfirmed) return

            // TODO 
            let start = picker.getStartDate()
            let end = picker.getEndDate()
            //API.setAvailability()

            //calendar.refetchEvents()
        })
    }

    static initModal() {
        BulkEdit.picker = new Litepicker({
            element: document.getElementById('litepicker'),
            inlineMode: true,
            singleMode: false,
        })
        document.getElementById("bulkEditStart").value = defaultStart
        document.getElementById("bulkEditEnd").value = defaultEnd

        buildGroupDropdown("bulkEditGroup", Swal.isVisible)
        buildLocationDropdown("bulkEditLocation", Swal.isVisible)
        buildProviderDropdown("bulkEditProvider", Swal.isVisible)

    }

}

export default BulkEdit