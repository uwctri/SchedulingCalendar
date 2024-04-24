// This class is used to wrap interfacing with native RC functions and
// libraries that ship with RC. 

class RedCap {

    static router = ExternalModules.UWMadison.Scheduling?.router
    static user = ExternalModules.UWMadison.Scheduling?.user

    static tt = ExternalModules.UWMadison.Scheduling?.tt // Redcap EM translate func
    static csrf = get_csrf_token // Redcap function
    static popover = (target, obj) => jQuery(target).popover(obj) // Bootstrap popovers use Jquery

}

export default RedCap