import Swal from 'sweetalert2'
import RedCap from './redcap'
import API from "./api"
import template from './html/userColors.html'
import PopOver from "./popover"
import iro from '@jaames/iro'

const html = RedCap.ttHTML(template)
const borderColor = "#000"
const pickerWidth = 250
const hexRegex = /^#([0-9a-f]{6}|[0-9a-f]{3})$/i

// Note: Once a color is set we don't allow it to go back to random
class ColorConfig {

    static metadata = null
    // Great contrast colors from ...
    // https://sashamaps.net/docs/resources/20-colors/
    static accessableColors = [
        "#e6194B", // Red
        "#3cb44b", // Green
        //"#ffe119", // Yellow
        "#4363d8", // Blue
        "#f58231", // Orange
        "#42d4f4", // Cyanf
        // "#f032e6", // Magenta
        "#fabed4", // Pink
        "#469990", // Teal
        "#dcbeff", // Lavender
        "#9A6324", // Brown
        "#fffac8", // Beige
        "#800000", // Maroon
        "#aaffc3", // Mint
        //"#000075", // Navy
        "#a9a9a9", // Grey
    ]
    static _accessableColors

    static getRandomColor = () => '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0')
    static getRandomAccessableColor = () => {
        if (!ColorConfig._accessableColors) {
            ColorConfig._accessableColors = ColorConfig.accessableColors.slice()
                .map(value => ({ value, sort: Math.random() }))
                .sort((a, b) => a.sort - b.sort)
                .map(({ value }) => value)
        }
        return ColorConfig._accessableColors.pop()
    }

    static open() {

        // No need for an init, just hit the cache
        let providerPromise = API.providers()
        let metadataPromise = API.metadata()
        let metadata
        let colorPicker

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
                metadata: metadata
            }).then(() => {
                // Reload Page to reinit the cal colors
                location.reload()
            })
        })

        // Create list of users rows
        Promise.all([providerPromise, metadataPromise]).then(([providers, metaReturn]) => {
            ColorConfig.metadata = ColorConfig.metadata == null ? { ...metaReturn.data } : ColorConfig.metadata
            metadata = metaReturn.data
            let template = $.getElementByClassName("userRow")
            for (const k in providers) {
                // Build the HTML
                let row = template.cloneNode(true)
                row.classList.remove("hidden")
                row.querySelector(".username").innerText = providers[k].label
                const color = metadata[providers[k].value]?.color
                const colorHex = !color ? RedCap.tt("color_random") : color
                const originalColor = ColorConfig.metadata[providers[k].value]?.color
                row.querySelector(".colorHex").value = colorHex
                row.querySelector(".colorHex").setAttribute("data-original", originalColor)
                row.querySelector(".colorSwatch").style.backgroundColor = color

                // Setup Hex input
                row.querySelector(".colorHex").addEventListener("keyup", (event) => {
                    const hex = event.target.value.toLowerCase()
                    if (!hex.match(hexRegex)) return
                    row.querySelector(".colorSwatch").style.backgroundColor = hex
                    metadata[providers[k].value] = { color: hex }
                    colorPicker.color.hexString = hex
                })

                // Setup hex focus
                row.querySelector(".colorHex").addEventListener("focus", (event) => {
                    const hex = event.target.value.toLowerCase()
                    if (!hex.match(hexRegex)) return
                    colorPicker.color.hexString = hex
                })

                // Setup Random and Reset buttons
                row.querySelector(".colorRandom").addEventListener("click", () => {
                    const el = row.querySelector(".colorHex")
                    el.value = ColorConfig.getRandomColor()
                    el.dispatchEvent(new Event('keyup'))
                })

                // Setup Reset button
                row.querySelector(".colorReset").addEventListener("click", () => {
                    const el = row.querySelector(".colorHex")
                    const value = el.getAttribute("data-original")
                    el.value = value && value != "undefined" ? value : RedCap.tt("color_random")
                    el.dispatchEvent(new Event('keyup'))
                })

                template.parentNode.appendChild(row)
            }
        })

        // Setup color picker
        colorPicker = new iro.ColorPicker("#picker", {
            width: pickerWidth,
            borderWidth: 1,
            borderColor: borderColor,
        })

        // When the color picker changes, update the color string
        colorPicker.on('color:change', (color) => {
            const el = $.activeElement
            if (!el.classList.contains("colorHex")) return
            el.value = color.hexString
            el.dispatchEvent(new Event('keyup'))
        })
    }
}

export default ColorConfig