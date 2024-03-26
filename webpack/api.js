import { CRUD, Resource } from "./enums"
import Loading from "./loading";

class API {

    static _time_fields = ["start_time", "end_time", "start", "end"];
    static _availabilityCodes = null;
    static _providers = null;
    static _locations = null;

    static async availabilityCodes() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.AvailabilityCode
        }

        if (API._availabilityCodes) return API._availabilityCodes

        const result = await API.post(data)
        API._availabilityCodes = result
        return result
    }


    static async providers() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Provider,
        }

        if (API._providers) return API._providers

        const result = await API.post(data)
        API._providers = result
        return result
    }

    static async subjects(provider = "") {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Subject,
            "provider": provider,
        }

        const result = await API.post(data)
        return result
    }

    static async locations() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Location
        }

        if (API._locations) return API._locations

        const result = await API.post(data)
        API._locations = result
        return result
    }

    static async getAvailability(payload) {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Availability,
            ...payload
        }

        return await API.post(data)
    }

    static async setAvailability(payload) {

        const data = {
            "crud": CRUD.Create,
            "resource": Resource.Availability,
            ...payload
        }

        return await API.post(data)
    }

    static async deleteAvailability(payload) {

        const data = {
            "crud": CRUD.Delete,
            "resource": Resource.Availability,
            ...payload
        }

        return await API.post(data)
    }

    static async post(data) {

        let result = {}
        data["redcap_csrf_token"] = csrf

        // Format times to be compatible with Postgress Timestamps
        // Trash the microseconds and swap T for space
        for (const [key, value] of Object.entries(data)) {
            if (API._time_fields.includes(key)) {
                data[key] = value.split('.')[0].replace("T", " ")
            }
        }

        Loading.show()
        await fetch(router, {
            method: 'POST',
            body: API.toFormData(data)
        }).then((response) => {
            if (response.ok) {
                return response.json()
            }
            return Promise.reject(response)
        }).then((data) => {
            result = data
            Loading.hide()
            console.log(data)
        }).catch((error) => {
            console.warn('Something went wrong in API.js', error, data)
        })

        return result
    }

    static toFormData(obj) {

        const data = new FormData()
        for (const [key, value] of Object.entries(obj)) {
            data.append(key, value)
        }
        return data
    }

}

export default API