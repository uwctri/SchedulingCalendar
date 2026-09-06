// This class is used to wrap interfacing with native RC functions and
// libraries that ship with RC. We also use the FontAwesome that ships with RC


/**
 * Disable Vanderbilt REDCap's ajax queueing because it is not compatible with our code.
 * See note from Vandy:
 * We queue ajax requests because concurrent requests for the same browser tab
 * sometimes fail because they trigger REDCap's duplicate query protection.
 * See this issue for details: https://github.com/vanderbilt-redcap/external-module-framework/issues/619
 */
window.ExternalModules.__ajaxQueue = (fn) => fn()

class RedCap {

    static module = ExternalModules.UWMadison.Scheduling
    static user = ExternalModules.UWMadison.Scheduling.user
    static project_name = ExternalModules.UWMadison.Scheduling.project_name
    static email = ExternalModules.UWMadison.Scheduling.email
    static timezones = ExternalModules.UWMadison.Scheduling.timezones
    static initialData = ExternalModules.UWMadison.Scheduling.initialData
    static btn_color = $.getElementById("content") ? getComputedStyle($.getElementById("content")).getPropertyValue("--redcap-btn-color") : ""
    static tt = (key, ...args) => ExternalModules.UWMadison.Scheduling.tt(key, ...args) // Redcap EM translate func

    static ttHTML = (html, data = {}) => {
        if (!html) return ""
        return html.replace(/{{(.*?)}}/g, (match, p1) => {
            const key = p1.trim()
            if (data) {
                if (data[key] !== undefined) return data[key]
                if (data[key.toLowerCase()] !== undefined) return data[key.toLowerCase()]
                if (data[key.toUpperCase()] !== undefined) return data[key.toUpperCase()]
            }
            const translated = RedCap.tt(`html_${key}`)
            return translated !== undefined && translated !== null ? translated : match
        })
    }
    static csrf = typeof get_csrf_token !== "undefined" ? get_csrf_token : null // Redcap function
    static popover = (target, obj) => typeof jQuery !== "undefined" ? jQuery(target).popover(obj) : null // Bootstrap popovers use Jquery
    static ajax = (action, payload) => ExternalModules.UWMadison.Scheduling.ajax(action, payload)

    static _log = (level, ...args) => console[level]("[SchedulingCalendar JS]", ...args)
    static log = (...args) => RedCap._log("log", ...args)
    static warn = (...args) => RedCap._log("warn", ...args)
    static error = (...args) => RedCap._log("error", ...args)

}

export default RedCap