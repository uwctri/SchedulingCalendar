import Cookies from 'js-cookie'
import Swal from 'sweetalert2'
import html from './html/userConfig.html'

const redcapBtnColor = "#337ab7";

let openUserConfig = () => {
    // TODO modify the html and load cookies

    Swal.fire({
        title: "User Configuration",
        html: html,
        confirmButtonColor: redcapBtnColor,
        customClass: {
            container: 'userConfigModal'
        }
    }).then(() => {
        // TODO save to cookie
    });
}

export default openUserConfig