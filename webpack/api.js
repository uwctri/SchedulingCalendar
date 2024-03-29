import { CRUD, Resource } from "./enums"
import Loading from "./loading";
import { DateTime } from "luxon"

const throttle_msg = "Throttled. Resource requested too recently."
class API {

    static _time_fields = ["start_time", "end_time", "start", "end"];

    // Cache and throttle these gets
    static _availabilityCodes = {
        "data": null,
        "expire": null,
        "interval": 30
    }
    static _providers = {
        "data": null,
        "expire": null,
        "interval": 30
    }
    static _locations = {
        "data": null,
        "expire": null,
        "interval": 30
    }

    static timestamp() { return DateTime.now().toISO() }
    static futureTimestamp(minutes) { return DateTime.now().plus({ "minutes": minutes }).toISO() }

    static async availabilityCodes() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.AvailabilityCode
        }

        // Request was sent too recently
        if (API._availabilityCodes.expire && API._availabilityCodes.expire > API.timestamp()) {
            if (API._availabilityCodes.data)
                return API._availabilityCodes.data
            return Promise.reject(throttle_msg)
        }

        const result = await API.post(data)
        API._availabilityCodes.data = result
        API._availabilityCodes.expire = API.futureTimestamp(API._availabilityCodes.interval)
        return result
    }

    static async providers() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Provider,
        }

        // Request was sent too recently
        if (API._providers.expire && API._providers.expire > API.timestamp()) {
            if (API._providers.data)
                return API._providers.data
            return Promise.reject(throttle_msg)
        }

        const result = await API.post(data)
        API._providers.data = result
        API._providers.expire = API.futureTimestamp(API._providers.interval)
        return result
    }

    static async subjects(providers = []) {

        if (!Array.isArray(providers))
            providers = [providers]

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Subject,
            "providers": providers,
        }

        const result = await API.post(data)
        return result
    }

    static async locations() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Location
        }

        // Request was sent too recently
        if (API._locations.expire && API._locations.expire > API.timestamp()) {
            if (API._locations.data)
                return API._locations.data
            return Promise.reject(throttle_msg)
        }

        const result = await API.post(data)
        API._locations.data = result
        API._locations.expire = API.futureTimestamp(API._locations.interval)
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