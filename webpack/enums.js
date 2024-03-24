
function createEnum(values) {
    const enumObject = {};
    for (const val of values) {
        enumObject[val] = val.toLowerCase();
    }
    return Object.freeze(enumObject);
}

export const CRUD = createEnum(["Create", "Read", "Update", "Delete"])
export const Resource = createEnum(["Availability", "Appointment", "Provider", "Subject", "Location", "Event", "AvailabilityCode"])
