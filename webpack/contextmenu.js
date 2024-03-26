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

class ContextMenu {

    static availabilityMenu = [{
        label: "Delete Availability",
        action(o) {
            console.log(o)
            // TODO grab an id from the target
        },
    }]

    static attachContextMenu(el, options) {
        const contextMenu = document.createElement('ul')
        const hideOnResize = () => hideMenu(true)

        function hideMenu(e) {
            if (e === true || !contextMenu.contains(e.target)) {
                contextMenu.remove()
                document.removeEventListener('click', hideMenu)
                window.removeEventListener('resize', hideOnResize)
            }
        }

        const attachOption = (target, opt, jsEvent) => {
            const item = document.createElement('li')
            item.className = 'context-menu-item'
            item.innerHTML = `<span>${opt.label}</span>`
            item.addEventListener('click', e => {
                e.stopPropagation()
                if (!opt.subMenu || opt.subMenu.length === 0) {
                    opt.target = jsEvent.target
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

        const showMenu = (jsEvent, menuOptions) => {
            jsEvent.preventDefault()
            contextMenu.className = 'context-menu'
            contextMenu.innerHTML = ''
            menuOptions.forEach(opt => attachOption(contextMenu, opt, jsEvent))
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

        el.addEventListener('contextmenu', (jsEvent) => showMenu(jsEvent, options))
    }
}

export default ContextMenu