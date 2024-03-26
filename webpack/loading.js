
class Loading {

    static show() {
        document.getElementById("loader").classList.remove("d-none")
    }

    static hide() {
        document.getElementById("loader").classList.add("d-none")
    }

    static isVisible() {
        return !document.getElementById("loader").classList.contains("d-none")
    }

}

export default Loading