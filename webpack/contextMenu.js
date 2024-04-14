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

const rcBtnColor = getComputedStyle(document.getElementById("content")).getPropertyValue("--redcap-btn-color")
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
            label: "Delete Appointment",
            action(o) {
                const id = o.target.getAttribute('data-internal-id')
                o.target.remove()
                API.deleteAppointments({
                    id: id
                }).then((data) => {
                    Calendar.refresh()
                })
            },
        },
        {
            label: "Change Provider",
            action(o) {
                const id = o.target.getAttribute('data-internal-id')
                const fcEvent = calendar.getEventById(id)
                Swal.fire({
                    title: "Change Provider", // TODO collect new provider
                    confirmButtonColor: rcBtnColor,
                    confirmButtonText: "Save",
                }).then((result) => {
                    // Bail if save wasn't clicked
                    if (!result.isConfirmed) return
                    API.updateAppointments({
                        id: id,
                        providers: fcEvent.extendedProps.user,
                        locations: fcEvent.extendedProps.location,
                    })
                })
            },
        },
        {
            label: "Change Location",
            action(o) {
                const id = o.target.getAttribute('data-internal-id')
                const fcEvent = calendar.getEventById(id)
                // TODO get the new location
                API.updateAppointments({
                    id: id,
                    providers: fcEvent.extendedProps.user,
                    locations: fcEvent.extendedProps.location,
                })
            },
        },
    ]

    static closeAll() {
        document.querySelectorAll('.context-menu').forEach(menu => menu.remove())
    }

    static attachContextMenu(el, options) {
        const contextMenu = document.createElement('ul')
        const hideOnResize = () => hideMenu(true)

        const hideMenu = (e) => {
            if (e === true || !contextMenu.contains(e.target)) {
                contextMenu.remove()
                document.removeEventListener('click', hideMenu)
                window.removeEventListener('resize', hideOnResize)
            }
        }

        const attachOption = (target, opt, el) => {
            const item = document.createElement('li')
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
                const subMenu = document.createElement('ul')
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
            document.body.appendChild(contextMenu)

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
            document.addEventListener('click', hideMenu)
            window.addEventListener('resize', hideOnResize)
        }

        el.addEventListener('contextmenu', (jsEvent) => showMenu(el, jsEvent, options))
    }
}

export default ContextMenu