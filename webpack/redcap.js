// This class is used to wrap interfacing with native RC functions and
// libraries that ship with RC. We also use the FontAwesome that ships with RC

class RedCap {

    static router = ExternalModules.UWMadison.Scheduling?.router
    static user = ExternalModules.UWMadison.Scheduling?.user
    static project_name = ExternalModules.UWMadison.Scheduling?.project_name
    static email = ExternalModules.UWMadison.Scheduling?.email

    static tt = (key) => ExternalModules.UWMadison.Scheduling?.tt(key) // Redcap EM translate func
    static ttHTML = (html) => {
        let match
        const reg = /{{(.*?)}}/g;
        while ((match = reg.exec(html)) !== null) {
            html = html.replace(match[0], RedCap.tt(`html_${match[1]}`))
        }
        return html
    }
    static csrf = get_csrf_token // Redcap function
    static popover = (target, obj) => jQuery(target).popover(obj) // Bootstrap popovers use Jquery

}

export default RedCap