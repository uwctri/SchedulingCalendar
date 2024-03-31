// This class is used to wrap interfacing with native RC functions and
// libraries that ship with RC. 

class RedCap {

    static tt = ExternalModules.UWMadison.Scheduling?.tt // Redcap EM translate func
    static csrf = get_csrf_token // Redcap function
    static popover = (target, obj) => jQuery(target).popover(obj) // popper.js ships with Bootstrap as a jQuery plugin

}

export default RedCap