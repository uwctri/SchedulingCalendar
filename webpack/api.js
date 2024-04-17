import { CRUD, Resource } from "./enums"
import Calendar from "./calendar";
import RedCap from "./redcap";
import { DateTime } from "luxon"

const req_msg = "Missing required keys in payload object for API call"
class API {

    static _time_fields = ["start", "end"];

    // Cache and throttle these gets
    static cache = {
        availabilityCodes: {
            data: null,
            expire: null,
            promise: null,
            interval: 999
        },
        providers: {
            data: null,
            expire: null,
            promise: null,
            interval: 999
        },
        locations: {
            data: null,
            expire: null,
            promise: null,
            interval: 999
        },
        visits: {
            data: null,
            expire: null,
            promise: null,
            interval: 999
        },
        subjects: {
            data: null,
            expire: null,
            promise: null,
            interval: 5
        },
        availability: {
            data: null,
            // Cache is hit explicitly and updated on any pull
        },
        appointments: {
            data: null,
            // Cache is hit explicitly and updated on any pull
        }
    }

    static timestamp() { return DateTime.now().toISO() }
    static futureTimestamp(minutes) { return DateTime.now().plus({ "minutes": minutes }).toISO() }
    static requiredKeys(obj, keys) {
        let keyOptions = Array.isArray(keys[0]) ? keys : [keys]
        for (const keySet of keyOptions) {
            if (keySet.every(key => key in obj))
                return true
        }
        throw Error(req_msg)
    }

    static async updateCache(promise, cacheObj) {
        cacheObj.promise = promise
        const result = await promise
        cacheObj.promise = null
        cacheObj.data = result
        cacheObj.expire = API.futureTimestamp(cacheObj.interval)
        return result
    }

    static async availabilityCodes(payload) {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.AvailabilityCode,
            ...payload
        }

        API.requiredKeys(data, ["all_availability"])

        // Throttle, return cache, or store to cache
        const cache = API.cache.availabilityCodes
        if (cache.expire && cache.expire > API.timestamp())
            return cache.data
        if (cache.promise)
            return cache.promise
        const promise = API.post(data)
        return await API.updateCache(promise, cache)
    }

    static async providers() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Provider,
        }

        // Throttle, return cache, or store to cache
        const cache = API.cache.providers
        if (cache.expire && cache.expire > API.timestamp())
            return cache.data
        if (cache.promise)
            return cache.promise
        const promise = API.post(data)
        return await API.updateCache(promise, cache)
    }

    static async subjects(providers = []) {

        if (!Array.isArray(providers))
            providers = [providers]

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Subject,
            "providers": providers,
        }

        // Throttle, return cache, or store to cache
        const cache = API.cache.subjects
        if (cache.expire && cache.expire > API.timestamp())
            return cache.data
        if (cache.promise)
            return cache.promise
        const promise = API.post(data)
        return await API.updateCache(promise, cache)
    }

    static async locations() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Location
        }

        // Throttle, return cache, or store to cache
        const cache = API.cache.locations
        if (cache.expire && cache.expire > API.timestamp())
            return cache.data
        if (cache.promise)
            return cache.promise
        const promise = API.post(data)
        return await API.updateCache(promise, cache)
    }

    static async visits() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Visit
        }

        // Throttle, return cache, or store to cache
        const cache = API.cache.visits
        if (cache.expire && cache.expire > API.timestamp())
            return cache.data
        if (cache.promise)
            return cache.promise
        const promise = API.post(data)
        return await API.updateCache(promise, cache)
    }

    static async getAvailability(payload, useCache = false) {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Availability,
            ...payload
        }

        API.requiredKeys(data, ["start", "end", "providers", "locations", "all_availability"])

        if (useCache && API.cache.availability.data)
            return API.cache.availability.data

        const response = await API.post(data)
        API.cache.availability.data = response
        return response
    }

    static async setAvailability(payload) {

        const data = {
            "crud": CRUD.Create,
            "resource": Resource.Availability,
            ...payload
        }

        API.requiredKeys(data, ["start", "end", "providers", "locations", "group"])
        return await API.post(data)
    }

    static async deleteAvailability(payload) {

        const data = {
            "crud": CRUD.Delete,
            "resource": Resource.Availability,
            ...payload
        }

        API.requiredKeys(data, [["start", "end", "providers", "locations", "group"], ["id"]])
        return await API.post(data)
    }

    static async getAppointments(payload, useCache = false) {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Appointment,
            ...payload
        }

        API.requiredKeys(data, ["start", "end", "providers", "locations", "subjects", "visits"])

        if (useCache && API.cache.appointments.data)
            return API.cache.appointments.data

        const response = await API.post(data)
        API.cache.appointments.data = response
        return response
    }

    static async setAppointments(payload) {

        const data = {
            "crud": CRUD.Create,
            "resource": Resource.Appointment,
            ...payload
        }

        API.requiredKeys(data, ["start", "end", "providers", "locations", "subjects", "visits"])
        return await API.post(data)
    }

    static async updateAppointments(payload) {

        const data = {
            "crud": CRUD.Update,
            "resource": Resource.Appointment,
            ...payload
        }

        API.requiredKeys(data, ["id", "providers", "locations"])
        return await API.post(data)
    }

    static async deleteAppointments(payload) {

        const data = {
            "crud": CRUD.Delete,
            "resource": Resource.Appointment,
            ...payload
        }

        API.requiredKeys(data, [["start", "time", "subjects"], ["id"]])
        return await API.post(data)
    }

    static async multi(payload) {

        if (!("crud" in payload) || !("resource" in payload) || !("bundle" in payload))
            return Promise.reject("Poorly formatted Multi request")

        return await API.post(payload)
    }

    static async post(data) {

        let result = {}
        data["redcap_csrf_token"] = RedCap.csrf()

        // Format times to be compatible with Postgress Timestamps
        // Trash the microseconds and swap T for space
        // Swap bools to 1 or 0 
        const format = (obj) => {
            for (const [key, value] of Object.entries(obj)) {
                if (API._time_fields.includes(key)) {
                    obj[key] = value.split('.')[0].replace("T", " ")
                }
                if (typeof value == "boolean") {
                    obj[key] = value ? 1 : 0
                }
            }
        }
        format(data)
        if ("bundle" in data) {
            for (const obj of data["bundle"]) {
                format(obj)
            }
        }

        console.log(data)
        Calendar.showLoading()
        await fetch(RedCap.router, {
            method: 'POST',
            body: API.toFormData(data)
        }).then((response) => {
            if (response.ok) {
                return response.json()
            }
            return Promise.reject(response)
        }).then((data) => {
            result = data
            Calendar.hideLoading()
            console.log(data)
        }).catch((error) => {
            console.warn('Something went wrong in API.js', error, data)
        })

        return result
    }

    static toFormData(obj) {

        const form = new FormData()

        const phpArray = (obj, outerKey, depth) => {
            for (let [key, value] of Object.entries(obj)) {
                key = depth > 0 ? `[${key}]` : key
                if (typeof value == "object") {
                    phpArray(value, `${outerKey}${key}`, depth + 1)
                    continue
                }
                form.append(`${outerKey}${key}`, value)
            }
        }

        phpArray(obj, "", 0)
        return form
    }

}

export default API