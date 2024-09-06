import Swal from 'sweetalert2'
import RedCap from './redcap'
import API from "./api"
import template from './html/userColors.html'
import PopOver from "./popover"
import iro from '@jaames/iro'

const html = RedCap.ttHTML(template)
const borderColor = "#000"
const defaultColor = "#ffffff"
const defaultText = "Random"
const pickerWidth = 250

// TODO add a reset button to this to undo a change to a user's color
class ColorConfig {

    static _init = false
    static _metadata = {}

    static init() {
        if (ColorConfig._init) return
        ColorConfig._init = true
        API.metadata().then(metadata => {
            ColorConfig._metadata = metadata.data
        })
    }

    static open() {

        let colorPicker
        ColorConfig.init()
        PopOver.close()
        Swal.fire({
            title: RedCap.tt("color_title"),
            html: html,
            confirmButtonColor: RedCap.btn_color,
            confirmButtonText: RedCap.tt("save"),
            customClass: {
                container: 'userColorModal'
            }
        }).then((result) => {

            // Bail if save wasn't clicked
            if (!result.isConfirmed) return

            // Save the new values
            API.setMetadata({
                metadata: ColorConfig._metadata
            }).then(() => {
                // Reload Page to reinit the cal colors
                location.reload()
            })
        })

        const select = $.getElementById("userColorSelect")
        const colorInput = $.getElementById("userColorInput")

        // Create dropdown
        API.providers().then(providers => {
            for (const k in providers) {
                let option = $.createElement("option")
                option.value = providers[k].value
                option.text = providers[k].label
                select.add(option)
            }
        })

        // When the dropdown changes, update the color picker
        $.getElementById("userColorSelect").addEventListener("change", () => {
            const user = select.value
            let color = ColorConfig._metadata[user]?.color || defaultColor
            colorPicker.color.hexString = color
            color = !user ? "" : color
            color = color === defaultColor ? defaultText : color
            colorInput.value = color
        })

        // Setup color picker
        colorPicker = new iro.ColorPicker("#picker", {
            width: pickerWidth,
            borderWidth: 1,
            borderColor: borderColor,
        })

        // When the color picker changes, update the color string
        colorPicker.on('color:change', function (color) {
            colorInput.value = color.hexString
            ColorConfig.updateMetadata()
        })

        // When the color string changes, update the color picker
        colorInput.addEventListener("change", function () {
            const hex = colorInput.value
            if (!hex.match(/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/i))
                return
            colorPicker.color.hexString = colorInput.value
            ColorConfig.updateMetadata()
        })
    }

    static updateMetadata() {
        const user = $.getElementById("userColorSelect").value
        const color = $.getElementById("userColorInput").value
        if (!user || !color || ["#000", "#000000", "#fff", "#ffffff"].includes(color))
            return
        ColorConfig._metadata[user] = { color: color }
    }

}

export default ColorConfig