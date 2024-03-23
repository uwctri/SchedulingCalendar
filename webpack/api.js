import { CRUD, Resource } from "./enums"

class API {

    static async providers() {

        const data = {
            "crud": CRUD.Read,
            "resource": Resource.Provider,
        }

        const result = await API.post(data)
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

        const result = await API.post(data)
        return result

    }

    static async post(data) {

        let result = {}
        data["redcap_csrf_token"] = csrf;

        await fetch(router, {
            method: 'POST',
            body: API.toFormData(data)
        }).then((response) => {
            if (response.ok) {
                return response.json();
            }
            return Promise.reject(response)
        }).then((data) => {
            result = data
        }).catch((error) => {
            console.warn('Something went wrong in API.js', error, data)
        });

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