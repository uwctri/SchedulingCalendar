import { CRUD, Resource } from "./enums"
import Calendar from "./calendar"
import RedCap from "./redcap"
import { DateTime } from "luxon"
import Swal from 'sweetalert2-optimized'
import Page from "./page"
import schema from "../schema.json"

const req_msg = "Missing required keys in payload object for API call"
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    iconColor: 'white',
    customClass: {
        popup: 'colored-toast',
    },
    showConfirmButton: false,
    timer: 30 * 1000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('click', () => Swal.close())
    }
})

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
        subjectDetails: {
            stor: {},
            interval: 10
        },
        availability: {
            stor: {
                "hash": {
                    data: [],
                    expire: null,
                    promise: null,
                }
            },
            interval: 5,
        },
        appointments: {
            stor: {
                "hash": {
                    data: [],
                    expire: null,
                    promise: null,
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
    static expireSubjectDetailsCache() { API.cache.subjectDetails.stor = {} }
    static requiredKeys(obj) {
        let keyOptions = schema[obj.resource][obj.crud]
        for (const keySet of keyOptions)
            if (keySet.every(key => key in obj))
                return true
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

        API.requiredKeys(data)

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

        // No required keys

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
            "timezone": Page.tz || "local",
        }

        // No required keys

        // Throttle, return cache, or store to cache
        const cache = API.cache.subjects
        if (cache.expire && cache.expire > API.timestamp())
            return cache.data
        if (cache.promise)
            return cache.promise
        const promise = API.post(data)
        return await API.updateCache(promise, cache)
    }

    static async subjectDetails(record) {
        if (!record) return null

        const hash = String(record)
        let cache = API.cache.subjectDetails.stor[hash]
        if (cache && cache.expire > API.timestamp())
            return cache.data
        if (cache && cache.promise)
            return cache.promise

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Subject,
            "record": record,
            "timezone": Page.tz || "local",
        }

        const promise = API.post(data)
        API.cache.subjectDetails.stor[hash] = { promise: promise }
        const response = await promise
        API.cache.subjectDetails.stor[hash] = {
            data: response,
            expire: API.futureTimestamp(API.cache.subjectDetails.interval),
            promise: null,
        }
        return response
    }

    static async locations() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Location
        }

        // No required keys

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

        // No required keys

        // Throttle, return cache, or store to cache
        const cache = API.cache.visits
        if (cache.expire && cache.expire > API.timestamp())
            return cache.data
        if (cache.promise)
            return cache.promise
        const promise = API.post(data)
        return await API.updateCache(promise, cache)
    }

    static async getAvailability(payload, returnPromise = false) {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Availability,
            "timezone": Page.tz || "local",
            ...payload
        }

        API.requiredKeys(data)

        const hash = JSON.stringify(payload)
        let cache = API.cache.availability.stor[hash]
        if (cache && cache.expire > API.timestamp())
            return cache.data
        if (cache && cache.promise && returnPromise)
            return cache.promise

        const promise = API.post(data)
        API.cache.availability.stor[hash] = { promise: promise }
        const response = await promise
        API.cache.availability.stor[hash] = {
            data: response,
            expire: API.futureTimestamp(API.cache.availability.interval),
            promise: null,
        }
        return response
    }

    static async setAvailability(payload) {

        const data = {
            "crud": CRUD.Create,
            "resource": Resource.Availability,
            "timezone": Page.tz || "local",
            ...payload
        }

        API.requiredKeys(data)
        API.expireAvailabilityCache()
        return await API.post(data)
    }

    static async deleteAvailability(payload) {

        const data = {
            "crud": CRUD.Delete,
            "resource": Resource.Availability,
            "timezone": Page.tz || "local",
            ...payload
        }

        API.requiredKeys(data)
        API.expireAvailabilityCache()
        return await API.post(data)
    }

    static async updateAvailability(payload) {

        const data = {
            "crud": CRUD.Update,
            "resource": Resource.Availability,
            "timezone": Page.tz || "local",
            ...payload
        }

        API.requiredKeys(data)
        API.expireAvailabilityCache()
        return await API.post(data)
    }

    static async getAppointments(payload, returnPromise = false) {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Appointment,
            "timezone": Page.tz || "local",
            ...payload
        }

        API.requiredKeys(data)

        const hash = JSON.stringify(payload)
        let cache = API.cache.appointments.stor[hash]
        if (cache && cache.expire > API.timestamp())
            return cache.data
        if (cache && cache.promise && returnPromise)
            return cache.promise

        const promise = API.post(data)
        API.cache.appointments.stor[hash] = { promise: promise }
        const response = await promise
        API.cache.appointments.stor[hash] = {
            data: response,
            expire: API.futureTimestamp(API.cache.appointments.interval),
            promise: null,
        }
        return response
    }

    static async setAppointments(payload) {

        const data = {
            "crud": CRUD.Create,
            "resource": Resource.Appointment,
            "timezone": Page.tz || "local",
            ...payload
        }

        API.requiredKeys(data)
        API.expireAvailabilityCache()
        API.expireAppointmentsCache()
        API.expireSubjectDetailsCache()
        return await API.post(data)
    }

    static async updateAppointments(payload) {

        const data = {
            "crud": CRUD.Update,
            "resource": Resource.Appointment,
            "timezone": Page.tz || "local",
            ...payload
        }

        API.requiredKeys(data)
        API.expireAvailabilityCache()
        API.expireAppointmentsCache()
        API.expireSubjectDetailsCache()
        return await API.post(data)
    }

    static async deleteAppointments(payload) {

        const data = {
            "crud": CRUD.Delete,
            "resource": Resource.Appointment,
            "timezone": Page.tz || "local",
            ...payload
        }

        API.requiredKeys(data)
        API.expireAvailabilityCache()
        API.expireAppointmentsCache()
        API.expireSubjectDetailsCache()
        return await API.post(data)
    }

    static async metadata(payload) {
        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Metadata
        }

        // No required keys

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

        API.requiredKeys(data)
        return await API.post(data)
    }

    static async multi(payload) {

        if (!("crud" in payload) || !("resource" in payload) || !("bundle" in payload))
            return Promise.reject("Poorly formatted Multi request")

        const crud = payload.crud
        const reso = payload.resource

        if ([CRUD.Delete, CRUD.Create].includes(crud) && reso == Resource.Appointment) {
            API.expireAppointmentsCache()
            API.expireSubjectDetailsCache()
        }
        if ([CRUD.Delete, CRUD.Create].includes(crud) && reso == Resource.Availability)
            API.expireAvailabilityCache()

        return await API.post(payload)
    }

    static async post(data) {

        let result = {}
        data["redcap_csrf_token"] = RedCap.csrf()
        RedCap.log("SENDING", data)

        // Format times to be compatible with DB Timestamps
        // Trash the microseconds and swap T for space
        // Swap bools to 1 or 0
        const format = (obj) => {
            for (const [key, value] of Object.entries(obj)) {
                if (API._time_fields.includes(key) && typeof value === "string")
                    obj[key] = value.split('.')[0].replace("T", " ")
                if (typeof value == "boolean")
                    obj[key] = value ? 1 : 0
            }
        }
        format(data)
        if ("bundle" in data && Array.isArray(data["bundle"]))
            for (const obj of data["bundle"])
                format(obj)

        Calendar.showLoading()
        await RedCap.ajax('calendar-api', data).then((data) => {
            if (typeof data === "string")
                try { data = JSON.parse(data) } catch (e) { }
            const success = data?.success ?? true
            result = data
            Calendar.hideLoading()
            RedCap[success ? 'log' : 'warn'](data)
            if (success) return
            Toast.fire({
                icon: 'warning',
                title: data?.msg || 'Unable to perform action',
            })
        }).catch((error) => {
            Calendar.hideLoading()
            const errorMsg = error?.responseJSON?.msg || error?.message || 'Fatal Server Error'
            Toast.fire({
                icon: 'error',
                title: errorMsg,
            })
            RedCap.error('Something went wrong in API.js', error, data)
        })

        return result
    }

    static initInitialData() {
        const initial = RedCap.initialData
        if (!initial) return
        if (initial.metadata) {
            API.cache.metadata.data = initial.metadata
            API.cache.metadata.expire = API.futureTimestamp(API.cache.metadata.interval)
        }
        if (initial.providers) {
            API.cache.providers.data = initial.providers
            API.cache.providers.expire = API.futureTimestamp(API.cache.providers.interval)
        }
        if (initial.locations) {
            API.cache.locations.data = initial.locations
            API.cache.locations.expire = API.futureTimestamp(API.cache.locations.interval)
        }
        if (initial.visits) {
            API.cache.visits.data = initial.visits
            API.cache.visits.expire = API.futureTimestamp(API.cache.visits.interval)
        }
    }

}

API.initInitialData()

export default API