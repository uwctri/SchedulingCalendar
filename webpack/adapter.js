// This class is used to wrap interfacing with native RC functions and
// libraries that ship with RC. 

class Adapter {

    static csrf = get_csrf_token // Redcap function
    static popover = (target, obj) => jQuery(target).popover(obj) // popper.js ships with Bootstrap as a jQuery plugin
    static listenShowModal = (target, callback) => jQuery(target).on("show.bs.modal", callback) // jQuery event listener

}

export default Adapter