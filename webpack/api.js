import { CRUD, Resource } from "./enums"
import Calendar from "./calendar"
import RedCap from "./redcap"
import { DateTime } from "luxon"

const req_msg = "Missing required keys in payload object for API call"
class API {

    static _time_fields = ["start", "end"]

    // Cache and throttle
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
            interval: 10
        },
        availability: {
            stor: {
                "hash": {
                    expire: "",
                    data: [],
                }
            },
            interval: 5,
        },
        appointments: {
            stor: {
                "hash": {
                    expire: "",
                    data: [],
                }
            },
            interval: 5,
        },
        metadata: {
            data: null,
            expire: null,
            promise: null,
            interval: 999
        },
    }

    static timestamp() { return DateTime.now().toISO() }
    static futureTimestamp(minutes) { return DateTime.now().plus({ "minutes": minutes }).toISO() }
    static expireAvailabilityCache() { API.cache.availability.stor = {} }
    static expireAppointmentsCache() { API.cache.appointments.stor = {} }
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

    static async getAvailability(payload) {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Availability,
            ...payload
        }

        API.requiredKeys(data, ["start", "end", "providers", "locations", "all_availability"])

        const hash = JSON.stringify(payload)
        let cache = API.cache.availability.stor[hash]
        if (cache && cache.expire > API.timestamp())
            return cache.data

        const promise = API.post(data)
        const response = await promise
        API.cache.availability.stor[hash] = {
            data: response,
            expire: API.futureTimestamp(API.cache.availability.interval),
            promise: null
        }
        return response
    }

    static async setAvailability(payload) {

        const data = {
            "crud": CRUD.Create,
            "resource": Resource.Availability,
            ...payload
        }

        API.requiredKeys(data, ["start", "end", "providers", "locations", "group"])
        API.expireAvailabilityCache()
        return await API.post(data)
    }

    static async deleteAvailability(payload) {

        const data = {
            "crud": CRUD.Delete,
            "resource": Resource.Availability,
            ...payload
        }

        API.requiredKeys(data, [["start", "end", "providers", "locations", "group"], ["id"]])
        API.expireAvailabilityCache()
        return await API.post(data)
    }

    static async getAppointments(payload) {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Appointment,
            ...payload
        }

        API.requiredKeys(data, ["start", "end", "providers", "locations", "subjects", "visits", "all_appointments"])

        const hash = JSON.stringify(payload)
        let cache = API.cache.appointments.stor[hash]
        if (cache && cache.expire > API.timestamp())
            return cache.data

        const promise = API.post(data)
        const response = await promise
        API.cache.appointments.stor[hash] = {
            data: response,
            expire: API.futureTimestamp(API.cache.appointments.interval),
        }
        return response
    }

    static async setAppointments(payload) {

        const data = {
            "crud": CRUD.Create,
            "resource": Resource.Appointment,
            ...payload
        }

        API.requiredKeys(data, ["start", "end", "providers", "locations", "subjects", "visits", "notes"])
        API.expireAppointmentsCache()
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

        API.requiredKeys(data, [["start", "end", "subjects"], ["id"]])
        API.expireAppointmentsCache()
        return await API.post(data)
    }

    static async metadata(payload) {
        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Metadata,
            ...payload
        }

        // Throttle, return cache, or store to cache
        const cache = API.cache.metadata
        if (cache.expire && cache.expire > API.timestamp())
            return cache.data
        if (cache.promise)
            return cache.promise
        const promise = API.post(data)
        return await API.updateCache(promise, cache)
    }

    static async setMetadata(payload) {
        const data = {
            "crud": CRUD.Update,
            "resource": Resource.Metadata,
            ...payload
        }

        API.requiredKeys(data, ["metadata"])
        return await API.post(data)
    }

    static async multi(payload) {

        if (!("crud" in payload) || !("resource" in payload) || !("bundle" in payload))
            return Promise.reject("Poorly formatted Multi request")

        const crud = payload.crud
        const reso = payload.resource

        if ([CRUD.Delete, CRUD.Create].includes(crud) && reso == Resource.Appointment)
            API.expireAppointmentsCache()
        if ([CRUD.Delete, CRUD.Create].includes(crud) && reso == Resource.Availability)
            API.expireAvailabilityCache()

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
            return response.ok ? response.json() : Promise.reject(response)
        }).then((data) => {
            // TODO when we return false trying to set an appointment/availability we should 
            // do something w/ the data
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