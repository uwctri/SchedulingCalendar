let Page = Object.fromEntries(new URLSearchParams(location.search))
Page.type = Page.type || "schedule"

export default Page