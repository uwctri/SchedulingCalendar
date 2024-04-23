// Lifted from a codepen ...
// https://codepen.io/beforesemicolon/pen/abNYjKo

// attachContextMenu(btn, [
//     {
//         label: "Open Link", action(o) { console.log(o) },
//         subMenu: [
//             { label: 'New Tab', action(o) { console.log(o) } },
//             { label: "New Window", action(o) { console.log(o) } },
//             { label: "This Tab", action(o) { console.log(o) } }
//         ]
//     },
//     {
//         label: "Download", action(o) { console.log(o) },
//         subMenu: [
//             { label: 'png', action(o) { console.log(o) } },
//             { label: 'jpg', action(o) { console.log(o) } },
//             { label: 'svg', action(o) { console.log(o) } }
//         ]
//     }
// ])

import API from './api.js'
import Swal from 'sweetalert2'
import Calendar from './calendar';
import html from './html/modify_appointment.html'
import { goToRecord } from './page.js';
import { buildLocationDropdown, buildProviderDropdown, savingAnimation } from "./utils";

const rcBtnColor = getComputedStyle($.getElementById("content")).getPropertyValue("--redcap-btn-color")
const loadingDots = `<div class="loading-dots"></div>`
class ContextMenu {

    static availabilityMenu = [{
        label: "Delete Availability",
        action(o) {
            const id = o.target.getAttribute('data-internal-id')
            o.target.remove()
            API.deleteAvailability({
                id: id
            }).then((data) => {
                Calendar.refresh()
            })
        },
    }]

    static appointmentMenu = [
        {
            label: "Open Record",
            action(el) {
                const id = el.target.getAttribute('data-internal-id')
                const details = Calendar.getEvent(id)
                goToRecord(details.extendedProps.record, details.extendedProps.project_id)
            },
        },
        {
            label: "Delete Appointment",
            action(el) {
                ContextMenu.deleteModal(el)
            },
        },
        {
            label: "Change Provider",
            action(el) {
                ContextMenu.modifyModal(el, "provider")
            },
        },
        {
            label: "Change Location",
            action(el) {
                ContextMenu.modifyModal(el, "location")
            },
        },
    ]

    static readMenu = [
        {
            label: "Open Record",
            action(el) {
                const id = el.target.getAttribute('data-internal-id')
                const details = Calendar.getEvent(id)
                goToRecord(details.extendedProps.record, details.extendedProps.project_id)
            },
        },
    ]

    static deleteModal(el) {
        const id = el.target.getAttribute('data-internal-id')
        Swal.fire({
            icon: "warning",
            title: "Are you sure?",
            text: "Do you want to delete this appointment?",
            confirmButtonText: "Delete",
            confirmButtonColor: "#dc3741" //swal-deny color
        }).then((result) => {
            if (!result.isConfirmed)
                return
            el.target.remove()
            API.deleteAppointments({
                id: id
            }).then((data) => {
                Calendar.refresh()
            })
        });
    }

    static modifyModal(el, str) {
        const id = el.target.getAttribute('data-internal-id')
        const fcEvent = Calendar.getEvent(id)
        const title = str[0].toUpperCase() + str.slice(1)
        let modalUser = null
        let modalLoc = null
        Swal.fire({
            title: `Change ${title}`,
            html: html,
            confirmButtonColor: rcBtnColor,
            confirmButtonText: "Update",
            didOpen: () => {
                $.getElementById(`aPop${title}`).classList.remove('hidden')
                buildProviderDropdown("aPopProvider", Swal.isVisible)
                buildLocationDropdown("aPopLocation", Swal.isVisible)
            },
            preConfirm: () => {
                const btnEl = "swal2-confirm"
                modalUser = $.getElementById("aPopProvider").value
                modalLoc = $.getElementById("aPopLocation").value

                API.updateAppointments({
                    id: id,
                    providers: modalUser || fcEvent.extendedProps.user,
                    locations: modalLoc || fcEvent.extendedProps.location,
                }).then(data => {
                    Calendar.refresh()
                })

                savingAnimation(btnEl)
                setTimeout(Swal.close, 2000)
                return false
            }
        })
    }

    static closeAll() {
        $.querySelectorAll('.context-menu').forEach(menu => menu.remove())
    }

    static attachContextMenu(el, options) {
        const contextMenu = $.createElement('ul')
        const hideOnResize = () => hideMenu(true)

        const hideMenu = (e) => {
            if (e === true || !contextMenu.contains(e.target)) {
                contextMenu.remove()
                $.removeEventListener('click', hideMenu)
                window.removeEventListener('resize', hideOnResize)
            }
        }

        const attachOption = (target, opt, el) => {
            const item = $.createElement('li')
            item.className = 'context-menu-item'
            item.innerHTML = `<span>${opt.label}</span>`
            item.addEventListener('click', e => {
                e.stopPropagation()
                if (!opt.subMenu || opt.subMenu.length === 0) {
                    opt.target = el
                    opt.action(opt)
                    hideMenu(true)
                }
            })

            target.appendChild(item)

            if (opt.subMenu && opt.subMenu.length) {
                const subMenu = $.createElement('ul')
                subMenu.className = 'context-sub-menu'
                item.appendChild(subMenu)
                opt.subMenu.forEach(subOpt => attachOption(subMenu, subOpt))
            }
        }

        const showMenu = (el, jsEvent, menuOptions) => {
            jsEvent.preventDefault()
            ContextMenu.closeAll()
            contextMenu.className = 'context-menu'
            contextMenu.innerHTML = ''
            menuOptions.forEach(opt => attachOption(contextMenu, opt, el))
            $.body.appendChild(contextMenu)

            const { innerWidth, innerHeight } = window
            const { offsetWidth, offsetHeight } = contextMenu
            let x = 0
            let y = 0

            if (jsEvent.clientX >= (innerWidth / 2)) {
                contextMenu.classList.add('left')
            }

            if (jsEvent.clientY >= (innerHeight / 2)) {
                contextMenu.classList.add('top')
            }

            if (jsEvent.clientX >= (innerWidth - offsetWidth)) {
                x = '-100%'
            }

            if (jsEvent.clientY >= (innerHeight - offsetHeight)) {
                y = '-100%'
            }

            contextMenu.style.left = jsEvent.clientX + 'px'
            contextMenu.style.top = jsEvent.clientY + 'px'
            contextMenu.style.transform = `translate(${x}, ${y})`
            $.addEventListener('click', hideMenu)
            window.addEventListener('resize', hideOnResize)
        }

        el.addEventListener('contextmenu', (jsEvent) => showMenu(el, jsEvent, options))
    }
}

export default ContextMenu