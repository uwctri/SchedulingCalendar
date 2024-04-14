let Page = Object.fromEntries(new URLSearchParams(location.search))
Page.type = Page.type || "edit" // TODO swap to schedule

export default Page