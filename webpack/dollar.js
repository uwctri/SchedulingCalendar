// Lifted from bling.js 

const sugar = {
    addClass: function (names) {
        arguments.forEach(arg => this.classList.add(arg))
        return this
    },
    removeClass: function (names) {
        arguments.forEach(arg => this.classList.remove(arg))
        return this
    },
    on: function (names, fn, options) {
        names
            .split(' ')
            .forEach(name =>
                this.addEventListener(name, fn, options))
        return this
    },
    off: function (names, fn, options) {
        names
            .split(' ')
            .forEach(name =>
                this.removeEventListener(name, fn, options))
        return this
    },
    attr: function (attr, val) {
        if (val === undefined) return this.getAttribute(attr)

        val == null
            ? this.removeAttribute(attr)
            : this.setAttribute(attr, val)

        return this
    }
}

function $(query, $context = document) {
    let $nodes = query instanceof NodeList || Array.isArray(query)
        ? query
        : query instanceof HTMLElement || query instanceof SVGElement
            ? [query]
            : $context.querySelectorAll(query)

    if (!$nodes.length) $nodes = []

    return Object.assign(
        Array.from($nodes).map($el => Object.assign($el, sugar)),
        {
            val: function () {
                return $nodes[0].value
            },
            get: function (index = 0) {
                return $nodes[index]
            },
            addClass: function (names) {
                this.forEach($el => $el.addClass(names))
                return this
            },
            removeClass: function (names) {
                this.forEach($el => $el.removeClass(names))
                return this
            },
            on: function (names, fn, options) {
                this.forEach($el => $el.on(names, fn, options))
                return this
            },
            off: function (names, fn, options) {
                this.forEach($el => $el.off(names, fn, options))
                return this
            },
            attr: function (attrs, val) {
                if (typeof attrs === 'string' && val === undefined)
                    return this[0].attr(attrs)

                else if (typeof attrs === 'object')
                    this.forEach($el =>
                        Object.entries(attrs)
                            .forEach(([key, val]) =>
                                $el.attr(key, val)))

                else if (typeof attrs == 'string')
                    this.forEach($el => $el.attr(attrs, val))

                return this
            }
        }
    )
}

$.createElement = document.createElement

export default $