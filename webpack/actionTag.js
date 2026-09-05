import Swal from "sweetalert2-optimized"

const doc = document

class ActionTagScheduler {
    constructor(config) {
        this.config = config
        this.fields = config.fields || {}
        this.projectId = config.projectId
        this.record = config.record
        this.eventId = config.eventId
        this.instrument = config.instrument
        this.timezones = config.timezones || []
        console.log("[SchedulingCalendar JS] ActionTagScheduler initialized with config:", config)
        this.init()
    }

    async init() {
        this.injectStyles()
        for (const [fieldName, fieldConfig] of Object.entries(this.fields))
            await this.setupField(fieldName, fieldConfig)
    }

    async callAjax(payload) {
        console.log("[SchedulingCalendar JS] callAjax sending payload:", payload)
        if (window.ExternalModules?.UWMadison?.Scheduling?.ajax) {
            const res = await window.ExternalModules.UWMadison.Scheduling.ajax("survey-calendar-api", payload)
            console.log("[SchedulingCalendar JS] callAjax received response:", res)
            return res
        }
        throw new Error("REDCap ExternalModule AJAX not initialized on this page")
    }

    findFieldInput(fieldName) {
        return doc.querySelector(`input[name="${fieldName}"]`) ||
            doc.querySelector(`textarea[name="${fieldName}"]`) ||
            doc.getElementById(`${fieldName}-tr`) ||
            doc.querySelector(`tr#${fieldName}-tr`) ||
            doc.querySelector(`tr[sq_id="${fieldName}"]`) ||
            doc.querySelector(`[data-rc-field="${fieldName}"]`)
    }

    async setupField(fieldName, fieldConfig) {
        console.log("[SchedulingCalendar JS] setupField:", fieldName, fieldConfig)
        const inputEl = this.findFieldInput(fieldName)
        if (!inputEl) {
            console.warn("[SchedulingCalendar JS] Could not find DOM element for field:", fieldName)
            return
        }
        console.log("[SchedulingCalendar JS] Found DOM element for field:", fieldName, inputEl)

        // Container element
        let container = doc.createElement("div")
        container.className = "sc-action-tag-container my-2"
        container.dataset.field = fieldName

        // Insert container: handle descriptive fields (tr) and regular inputs differently
        if (inputEl.tagName === "TR") {
            const targetCell = inputEl.querySelector("td.data") ||
                inputEl.querySelector("td.labelrc") ||
                inputEl.querySelector("td") ||
                inputEl
            targetCell.appendChild(container)
            console.log("[SchedulingCalendar JS] Container appended into table cell:", targetCell)
        } else {
            const parentWrapper = inputEl.closest(".form-control-static") || inputEl.parentNode
            parentWrapper.appendChild(container)
            console.log("[SchedulingCalendar JS] Container appended next to input wrapper:", parentWrapper)
        }

        // Render loading state
        container.innerHTML = `
            <div class="sc-loading">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <div class="small">Checking appointment status...</div>
            </div>
        `

        // Check if existing appointment exists
        let appointment = null
        if (this.record) {
            try {
                const userTz = this.getResolvedTimezone(fieldConfig.timezone)
                console.log("[SchedulingCalendar JS] Checking existing appointment for record:", this.record, "visit:", fieldConfig.visit, "timezone:", userTz)
                const apptRes = await this.callAjax({
                    action: "get-appointment",
                    pid: this.projectId,
                    record: this.record,
                    visit: fieldConfig.visit,
                    timezone: userTz
                })
                if (apptRes?.has_appointment) {
                    appointment = apptRes.appointment
                    console.log("[SchedulingCalendar JS] Existing appointment found:", appointment)
                }
            } catch (err) {
                console.error("[SchedulingCalendar JS] Error fetching existing appointment:", err)
            }
        }

        this.renderWidget(container, inputEl, fieldConfig, appointment)
        this.setupPipingWatchers(container, inputEl, fieldConfig)
    }

    setupPipingWatchers(container, inputEl, fieldConfig) {
        const checkAndReload = () => {
            const scheduleBtn = container.querySelector(".sc-schedule-btn")
            if (scheduleBtn && container.querySelector(".sc-inline-container"))
                this.loadAndRenderSlots(container, inputEl, fieldConfig)
        }

        if (fieldConfig.piped_location) {
            const el = doc.querySelector(`[name="${fieldConfig.piped_location}"]`)
            if (el) el.addEventListener("change", checkAndReload)
        }
        if (fieldConfig.piped_provider) {
            const el = doc.querySelector(`[name="${fieldConfig.piped_provider}"]`)
            if (el) el.addEventListener("change", checkAndReload)
        }
    }

    getPipedValue(param, pipedParam) {
        if (pipedParam) {
            const el = doc.querySelector(`[name="${pipedParam}"]`)
            if (el && el.value) return el.value
        }
        return param
    }

    getResolvedTimezone(tz) {
        if (!tz || tz === "browser") {
            try {
                return Intl.DateTimeFormat().resolvedOptions().timeZone || "local"
            } catch (e) {
                return "local"
            }
        }
        return tz
    }

    renderWidget(container, inputEl, fieldConfig, appointment) {
        console.log("[SchedulingCalendar JS] renderWidget for field:", fieldConfig.field_name, "appointment:", appointment)
        container.innerHTML = ""

        if (appointment) {
            // Already booked - centered display card
            const wrapper = doc.createElement("div")
            wrapper.className = "d-flex justify-content-center my-2"

            const card = doc.createElement("div")
            card.className = "sc-appointment-card alert alert-info p-3 mb-0 shadow-sm"
            card.style.maxWidth = "700px"
            card.style.width = "100%"
            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1 font-weight-bold text-dark">
                            <i class="fas fa-calendar-check text-success mr-1"></i> Scheduled Appointment
                        </h6>
                        <div class="sc-appt-details text-secondary small">
                            <div><strong>Date & Time:</strong> ${appointment.date_display} (${appointment.time_display})</div>
                            ${appointment.location_name ? `<div><strong>Location:</strong> ${appointment.location_name}</div>` : ""}
                            ${!fieldConfig.hide_provider && appointment.provider_name ? `<div><strong>Provider:</strong> ${appointment.provider_name}</div>` : ""}
                        </div>
                    </div>
                    <div class="sc-appt-actions text-right">
                        ${fieldConfig.allow_reschedule ? `<button type="button" class="btn btn-sm btn-outline-primary sc-reschedule-btn mb-1"><i class="fas fa-edit"></i> Reschedule</button>` : ""}
                        ${fieldConfig.allow_cancel ? `<button type="button" class="btn btn-sm btn-outline-danger sc-cancel-btn"><i class="fas fa-times"></i> Cancel</button>` : ""}
                    </div>
                </div>
            `

            if (fieldConfig.allow_reschedule) {
                card.querySelector(".sc-reschedule-btn")?.addEventListener("click", () => {
                    this.renderBookingInterface(container, inputEl, fieldConfig, true, appointment)
                })
            }

            if (fieldConfig.allow_cancel) {
                card.querySelector(".sc-cancel-btn")?.addEventListener("click", async () => {
                    const confirm = await Swal.fire({
                        title: "Cancel Appointment?",
                        text: "Are you sure you want to cancel this scheduled appointment?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: "Yes, Cancel"
                    })
                    if (confirm.isConfirmed) {
                        try {
                            const res = await this.callAjax({
                                action: "cancel-appointment",
                                pid: this.projectId,
                                record: this.record,
                                id: appointment.id
                            })
                            if (res?.success) {
                                if (inputEl.tagName !== "TR" && "value" in inputEl) {
                                    inputEl.value = ""
                                    inputEl.dispatchEvent(new Event("change", { bubbles: true }))
                                }
                                Swal.fire("Cancelled", "Your appointment has been cancelled.", "success")
                                this.renderWidget(container, inputEl, fieldConfig, null)
                            } else {
                                Swal.fire("Error", res?.msg || "Failed to cancel appointment", "error")
                            }
                        } catch (err) {
                            Swal.fire("Error", err.message || "An error occurred", "error")
                        }
                    }
                })
            }

            wrapper.appendChild(card)
            container.appendChild(wrapper)
            return
        }

        this.renderBookingInterface(container, inputEl, fieldConfig, false)
    }

    renderBookingInterface(container, inputEl, fieldConfig, isRescheduling = false, oldAppt = null) {
        console.log("[SchedulingCalendar JS] renderBookingInterface:", fieldConfig.field_name, "mode:", fieldConfig.mode)
        container.innerHTML = ""

        if (fieldConfig.mode === "popup") {
            const btnWrap = doc.createElement("div")
            btnWrap.className = "sc-popup-wrapper text-center my-3 p-4 bg-light rounded border d-flex flex-column align-items-center justify-content-center shadow-sm"
            btnWrap.style.margin = "0 auto"
            btnWrap.style.maxWidth = "700px"
            btnWrap.style.width = "100%"
            btnWrap.style.boxSizing = "border-box"

            const promptText = doc.createElement("div")
            promptText.className = "text-muted mb-2 font-weight-bold"
            promptText.innerHTML = `<i class="fas fa-calendar-check text-primary mr-1"></i> ${isRescheduling ? "Reschedule your appointment:" : "Schedule an appointment:"}`

            const btn = doc.createElement("button")
            btn.type = "button"
            btn.className = "btn btn-primary btn-lg sc-schedule-btn px-4 py-2 shadow-sm rounded-pill font-weight-bold"
            btn.innerHTML = `<i class="fas fa-calendar-alt mr-2"></i> ${isRescheduling ? "Choose New Date & Time" : fieldConfig.btn_text}`
            btn.addEventListener("click", () => this.openModal(container, inputEl, fieldConfig, isRescheduling, oldAppt))

            const badge = doc.createElement("div")
            badge.className = "sc-selected-badge badge bg-success text-white d-none mt-3 shadow-sm mx-auto"
            badge.style.maxWidth = "100%"

            btnWrap.appendChild(promptText)
            btnWrap.appendChild(btn)
            btnWrap.appendChild(badge)

            if (isRescheduling && oldAppt) {
                const cancelRescheduleBtn = doc.createElement("button")
                cancelRescheduleBtn.type = "button"
                cancelRescheduleBtn.className = "btn btn-sm btn-outline-secondary mt-3"
                cancelRescheduleBtn.innerHTML = `<i class="fas fa-undo mr-1"></i> Keep Current Appointment`
                cancelRescheduleBtn.addEventListener("click", () => {
                    this.renderWidget(container, inputEl, fieldConfig, oldAppt)
                })
                btnWrap.appendChild(cancelRescheduleBtn)
            }

            container.appendChild(btnWrap)
        } else {
            // inline mode - direct elements without nested card wrapper
            const inlineWrap = doc.createElement("div")
            inlineWrap.className = "sc-inline-container w-100"
            inlineWrap.innerHTML = `
                ${isRescheduling && oldAppt ? `
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary sc-cancel-resched">
                            <i class="fas fa-undo mr-1"></i> Keep Current Appointment
                        </button>
                    </div>
                ` : ""}
                <div class="d-flex justify-content-center w-100">
                    <div class="sc-selected-badge badge bg-success text-white d-none shadow-sm text-center mb-3"></div>
                </div>
                <div class="sc-slots-content">
                    <div class="sc-loading">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <div>Loading available slots...</div>
                    </div>
                </div>
            `

            if (isRescheduling && oldAppt) {
                inlineWrap.querySelector(".sc-cancel-resched")?.addEventListener("click", () => {
                    this.renderWidget(container, inputEl, fieldConfig, oldAppt)
                })
            }

            container.appendChild(inlineWrap)
            this.loadAndRenderSlots(inlineWrap.querySelector(".sc-slots-content"), inputEl, fieldConfig, container, isRescheduling)
        }
    }

    async openModal(container, inputEl, fieldConfig, isRescheduling, oldAppt) {
        console.log("[SchedulingCalendar JS] openModal clicked")
        let modalOverlay = doc.getElementById("sc-modal-overlay")
        if (!modalOverlay) {
            modalOverlay = doc.createElement("div")
            modalOverlay.id = "sc-modal-overlay"
            modalOverlay.className = "sc-modal-overlay"
            modalOverlay.innerHTML = `
                <div class="sc-modal-dialog">
                    <div class="sc-modal-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar-alt text-primary fa-lg mr-2"></i>
                            <h5 class="sc-modal-title m-0 font-weight-bold text-dark">${fieldConfig.field_label || "Select Appointment Time"}</h5>
                        </div>
                        <button type="button" class="sc-modal-close btn btn-link text-secondary p-0" aria-label="Close" style="font-size: 1.8rem; text-decoration: none; line-height: 1;">&times;</button>
                    </div>
                    <div class="sc-modal-body p-0 d-flex flex-column flex-grow-1" style="overflow: hidden; width: 100%;">
                        <div class="sc-slots-content d-flex flex-column flex-grow-1 h-100 w-100" style="width: 100%;">
                            <div class="sc-loading">
                                <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                                <h6 class="text-muted font-weight-normal mb-0">Loading available appointment times...</h6>
                            </div>
                        </div>
                    </div>
                </div>
            `
            doc.body.appendChild(modalOverlay)

            modalOverlay.querySelector(".sc-modal-close").addEventListener("click", () => {
                modalOverlay.classList.remove("sc-modal-open")
            })

            modalOverlay.addEventListener("click", (e) => {
                if (e.target === modalOverlay)
                    modalOverlay.classList.remove("sc-modal-open")
            })
        }

        modalOverlay.classList.add("sc-modal-open")
        const slotsContent = modalOverlay.querySelector(".sc-slots-content")
        slotsContent.innerHTML = `
            <div class="sc-loading">
                <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                <h6 class="text-muted font-weight-normal mb-0">Loading available appointment times...</h6>
            </div>
        `

        await this.loadAndRenderSlots(slotsContent, inputEl, fieldConfig, container, isRescheduling, () => {
            modalOverlay.classList.remove("sc-modal-open")
        })
    }

    async loadAndRenderSlots(targetElement, inputEl, fieldConfig, container, isRescheduling, closeModalFn) {
        const provider = this.getPipedValue(fieldConfig.provider, fieldConfig.piped_provider)
        const location = this.getPipedValue(fieldConfig.location, fieldConfig.piped_location)
        const userTz = this.getResolvedTimezone(fieldConfig.timezone)
        console.log("[SchedulingCalendar JS] loadAndRenderSlots:", { visit: fieldConfig.visit, start: fieldConfig.start, end: fieldConfig.end, provider, location, timezone: userTz })

        try {
            const res = await this.callAjax({
                action: "get-slots",
                pid: this.projectId,
                visit: fieldConfig.visit,
                start: fieldConfig.start,
                end: fieldConfig.end,
                duration: fieldConfig.duration,
                provider: provider,
                location: location,
                timezone: userTz
            })

            if (!res?.success || !res.slots || res.slots.length === 0) {
                console.log("[SchedulingCalendar JS] No slots available in range")
                targetElement.innerHTML = `
                    <div class="alert alert-warning text-center m-4 p-4 shadow-sm rounded">
                        <i class="fas fa-calendar-times fa-3x mb-3 text-warning"></i>
                        <h5 class="alert-heading font-weight-bold">No Appointments Currently Available</h5>
                        <p class="mb-0 text-muted">There are no open slots matching the scheduling window for this visit. Please check back later or contact study coordinators for assistance.</p>
                    </div>
                `
                return
            }

            console.log(`[SchedulingCalendar JS] Loaded ${res.slots.length} slots`)
            if (closeModalFn) {
                this.renderSlotExplorer(targetElement, res.slots, inputEl, fieldConfig, container, isRescheduling, closeModalFn, res.timezone || userTz)
            } else {
                this.renderInlineAccordion(targetElement, res.slots, inputEl, fieldConfig, container, isRescheduling, res.timezone || userTz)
            }
        } catch (err) {
            console.error("[SchedulingCalendar JS] Error loading slots:", err)
            targetElement.innerHTML = `<div class="alert alert-danger m-3 p-3">Error loading slots: ${err.message}</div>`
        }
    }

    renderInlineAccordion(targetElement, allSlots, inputEl, fieldConfig, container, isRescheduling, timezone) {
        targetElement.innerHTML = ""

        // Group slots by date_key
        const datesMap = new Map()
        const grouped = {}
        allSlots.forEach((s) => {
            if (!datesMap.has(s.date_key)) datesMap.set(s.date_key, s.date_display)
            if (!grouped[s.date_key]) grouped[s.date_key] = []
            grouped[s.date_key].push(s)
        })

        const sortedDates = Array.from(datesMap.keys()).sort()

        // Collapse dates if more than one. If exactly one, expand it.
        let activeDate = sortedDates.length === 1 ? sortedDates[0] : null

        const accordionWrapper = doc.createElement("div")
        accordionWrapper.className = "sc-inline-accordion"

        const subHeader = doc.createElement("div")
        subHeader.className = "d-flex justify-content-between align-items-center mb-3 text-muted small"
        subHeader.innerHTML = `
            <span><i class="far fa-calendar-check mr-1"></i> ${sortedDates.length} date${sortedDates.length === 1 ? "" : "s"} available</span>
            <span><i class="fas fa-clock mr-1"></i> ${timezone === "local" ? "Server Time" : timezone}</span>
        `
        accordionWrapper.appendChild(subHeader)

        const groupContainer = doc.createElement("div")
        groupContainer.className = "sc-accordion-group d-flex flex-column gap-2"

        const itemElements = new Map()

        sortedDates.forEach((dKey) => {
            const dateSlots = grouped[dKey]
            const dDisplay = datesMap.get(dKey) || dKey

            const item = doc.createElement("div")
            item.className = `sc-accordion-item border rounded bg-white shadow-sm ${activeDate === dKey ? "sc-accordion-open" : ""}`
            item.dataset.date = dKey

            const header = doc.createElement("button")
            header.type = "button"
            header.className = "sc-accordion-header w-100 p-3 bg-white border-0 text-left d-flex justify-content-between align-items-center"
            header.style.cursor = "pointer"
            header.style.borderRadius = "inherit"
            header.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="far fa-calendar-alt text-primary mr-2 fa-lg"></i>
                    <span class="font-weight-bold text-dark">${dDisplay}</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge badge-light border text-muted mr-2">${dateSlots.length} slot${dateSlots.length === 1 ? "" : "s"}</span>
                    <i class="fas fa-chevron-down text-secondary sc-accordion-chevron"></i>
                </div>
            `

            const body = doc.createElement("div")
            body.className = `sc-accordion-body p-3 bg-light border-top ${activeDate === dKey ? "" : "d-none"}`

            const grid = doc.createElement("div")
            grid.className = "sc-slots-grid"

            dateSlots.forEach((slot) => {
                const card = doc.createElement("div")
                card.className = "sc-slot-card shadow-sm"
                card.dataset.slotStart = slot.start
                card.innerHTML = `
                    <div class="sc-slot-time-wrap d-flex align-items-center mb-2">
                        <i class="far fa-clock text-primary mr-2 fa-lg"></i>
                        <span class="sc-slot-time">${slot.time_display}</span>
                    </div>
                    <div class="sc-slot-meta small">
                        ${slot.location_name ? `
                            <div class="text-secondary text-truncate mb-1" title="${slot.location_name}">
                                <i class="fas fa-map-marker-alt text-danger mr-1"></i> ${slot.location_name}
                            </div>
                        ` : ""}
                        ${!fieldConfig.hide_provider && slot.provider_name ? `
                            <div class="text-secondary text-truncate" title="${slot.provider_name}">
                                <i class="fas fa-user-md text-info mr-1"></i> ${slot.provider_name}
                            </div>
                        ` : ""}
                    </div>
                `

                card.addEventListener("click", async () => {
                    await this.selectSlot(slot, inputEl, fieldConfig, container, isRescheduling)
                    targetElement.querySelectorAll(".sc-slot-card").forEach((c) => c.classList.remove("sc-slot-selected"))
                    card.classList.add("sc-slot-selected")
                })

                grid.appendChild(card)
            })

            body.appendChild(grid)
            item.appendChild(header)
            item.appendChild(body)
            groupContainer.appendChild(item)

            itemElements.set(dKey, { item, body })

            header.addEventListener("click", () => {
                const isCurrentlyActive = activeDate === dKey
                // Only allow one day to be expanded at a time
                activeDate = isCurrentlyActive ? null : dKey

                itemElements.forEach(({ item: it, body: bd }, key) => {
                    if (key === activeDate) {
                        it.classList.add("sc-accordion-open")
                        bd.classList.remove("d-none")
                    } else {
                        it.classList.remove("sc-accordion-open")
                        bd.classList.add("d-none")
                    }
                })
            })
        })

        accordionWrapper.appendChild(groupContainer)
        targetElement.appendChild(accordionWrapper)
    }

    renderSlotExplorer(targetElement, allSlots, inputEl, fieldConfig, container, isRescheduling, closeModalFn, timezone) {
        targetElement.innerHTML = ""

        // Extract unique locations, providers, and dates
        const locations = new Map()
        const providers = new Map()
        const datesMap = new Map()

        allSlots.forEach((s) => {
            if (s.location) locations.set(s.location, s.location_name || s.location)
            if (s.provider) providers.set(s.provider, s.provider_name || s.provider)
            if (!datesMap.has(s.date_key)) datesMap.set(s.date_key, s.date_display)
        })

        const sortedDates = Array.from(datesMap.keys()).sort()
        const minDate = sortedDates[0] || ""
        const maxDate = sortedDates[sortedDates.length - 1] || ""

        // Filter state
        let filterState = {
            location: "",
            provider: "",
            date: "all"
        }

        const explorerWrapper = doc.createElement("div")
        explorerWrapper.className = "sc-explorer-wrapper d-flex flex-column flex-grow-1 h-100 w-100"
        explorerWrapper.style.width = "100%"

        // 1. Toolbar
        const toolbar = doc.createElement("div")
        toolbar.className = "sc-explorer-toolbar px-3 py-2 bg-light border-bottom d-flex flex-wrap align-items-center gap-3"

        // Location filter
        let locSelect = null
        if (locations.size > 1) {
            const locGroup = doc.createElement("div")
            locGroup.className = "sc-filter-group d-flex align-items-center"
            locGroup.innerHTML = `
                <label class="small font-weight-bold mb-0 mr-2 text-secondary text-nowrap">
                    <i class="fas fa-map-marker-alt text-danger mr-1"></i> Location:
                </label>
                <select class="form-control form-control-sm sc-filter-location" style="min-width: 170px;">
                    <option value="">All Locations (${locations.size})</option>
                    ${Array.from(locations.entries()).map(([code, name]) => `<option value="${code}">${name}</option>`).join("")}
                </select>
            `
            locSelect = locGroup.querySelector("select")
            toolbar.appendChild(locGroup)
        } else if (locations.size === 1) {
            const locItem = doc.createElement("div")
            locItem.className = "sc-filter-group small text-muted d-flex align-items-center"
            locItem.innerHTML = `<i class="fas fa-map-marker-alt text-danger mr-1"></i> <strong>Location:</strong>&nbsp;${Array.from(locations.values())[0]}`
            toolbar.appendChild(locItem)
        }

        // Provider filter
        let provSelect = null
        if (!fieldConfig.hide_provider && providers.size > 1) {
            const provGroup = doc.createElement("div")
            provGroup.className = "sc-filter-group d-flex align-items-center"
            provGroup.innerHTML = `
                <label class="small font-weight-bold mb-0 mr-2 text-secondary text-nowrap">
                    <i class="fas fa-user-md text-info mr-1"></i> Provider:
                </label>
                <select class="form-control form-control-sm sc-filter-provider" style="min-width: 170px;">
                    <option value="">All Providers (${providers.size})</option>
                    ${Array.from(providers.entries()).map(([user, name]) => `<option value="${user}">${name}</option>`).join("")}
                </select>
            `
            provSelect = provGroup.querySelector("select")
            toolbar.appendChild(provGroup)
        }

        // Jump to date picker
        const datePickerGroup = doc.createElement("div")
        datePickerGroup.className = "sc-filter-group d-flex align-items-center"
        datePickerGroup.innerHTML = `
            <label class="small font-weight-bold mb-0 mr-2 text-secondary text-nowrap">
                <i class="far fa-calendar-alt text-primary mr-1"></i> Jump to Date:
            </label>
            <input type="date" class="form-control form-control-sm sc-filter-date" min="${minDate}" max="${maxDate}">
        `
        const dateInput = datePickerGroup.querySelector("input")
        toolbar.appendChild(datePickerGroup)

        // Reset button
        const summaryGroup = doc.createElement("div")
        summaryGroup.className = "ml-auto d-flex align-items-center"
        summaryGroup.innerHTML = `
            <button type="button" class="btn btn-sm btn-outline-secondary sc-reset-btn d-none" title="Reset filters">
                <i class="fas fa-undo mr-1"></i> Reset
            </button>
        `
        const resetBtn = summaryGroup.querySelector(".sc-reset-btn")
        toolbar.appendChild(summaryGroup)

        explorerWrapper.appendChild(toolbar)

        // 2. Main 2-Column Split
        const mainSplit = doc.createElement("div")
        mainSplit.className = "sc-explorer-main d-flex flex-grow-1 w-100"
        mainSplit.style.minHeight = "0"
        mainSplit.style.overflow = "hidden"
        mainSplit.style.width = "100%"

        // Left Sidebar: Dates List
        const sidebar = doc.createElement("div")
        sidebar.className = "sc-dates-sidebar border-right bg-white p-2 d-flex flex-column"
        sidebar.style.width = "250px"
        sidebar.style.flexShrink = "0"
        sidebar.style.overflowY = "auto"
        sidebar.innerHTML = `
            <div class="small font-weight-bold text-uppercase text-muted px-2 py-1 mb-1">
                Available Dates
            </div>
            <div class="sc-dates-list d-flex flex-column gap-1"></div>
        `
        const datesListEl = sidebar.querySelector(".sc-dates-list")
        mainSplit.appendChild(sidebar)

        // Right Content: Slots Panel
        const slotsPanel = doc.createElement("div")
        slotsPanel.className = "sc-slots-panel flex-grow-1 p-3 bg-light d-flex flex-column"
        slotsPanel.style.overflowY = "auto"
        slotsPanel.innerHTML = `
            <div class="sc-slots-panel-header d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h6 class="sc-active-date-heading font-weight-bold m-0 text-dark">
                    Showing All Dates
                </h6>
                <span class="text-muted small">
                    <i class="fas fa-clock mr-1"></i> ${timezone === "local" ? "Server Time" : timezone}
                </span>
            </div>
            <div class="sc-slots-container"></div>
        `
        const activeHeadingEl = slotsPanel.querySelector(".sc-active-date-heading")
        const slotsContainerEl = slotsPanel.querySelector(".sc-slots-container")
        mainSplit.appendChild(slotsPanel)

        explorerWrapper.appendChild(mainSplit)
        targetElement.appendChild(explorerWrapper)

        // Rendering function
        const updateView = () => {
            console.log("[SchedulingCalendar JS] Filter state:", filterState)

            // 1. Get filtered slots
            const filtered = allSlots.filter((s) => {
                if (filterState.location && s.location !== filterState.location) return false
                if (filterState.provider && s.provider !== filterState.provider) return false
                if (filterState.date !== "all" && s.date_key !== filterState.date) return false
                return true
            })

            // Filtered slots grouped by date
            const grouped = {}
            filtered.forEach((s) => {
                if (!grouped[s.date_key]) grouped[s.date_key] = []
                grouped[s.date_key].push(s)
            })

            // Slots matching location/provider for date list counts
            const baseForDates = allSlots.filter((s) => {
                if (filterState.location && s.location !== filterState.location) return false
                if (filterState.provider && s.provider !== filterState.provider) return false
                return true
            })
            const dateCounts = {}
            baseForDates.forEach((s) => {
                dateCounts[s.date_key] = (dateCounts[s.date_key] || 0) + 1
            })

            // Update reset button
            const isFiltered = filterState.location || filterState.provider || filterState.date !== "all"
            if (isFiltered) {
                resetBtn.classList.remove("d-none")
            } else {
                resetBtn.classList.add("d-none")
            }

            // Update active heading
            if (filterState.date === "all") {
                activeHeadingEl.textContent = `Showing All Dates (${filtered.length} slots)`
            } else {
                const dDisplay = datesMap.get(filterState.date) || filterState.date
                activeHeadingEl.textContent = `${dDisplay} (${filtered.length} slots)`
            }

            // Render Dates Sidebar
            datesListEl.innerHTML = ""

            // "All Dates" button
            const allDatesBtn = doc.createElement("button")
            allDatesBtn.type = "button"
            allDatesBtn.className = `sc-date-nav-btn ${filterState.date === "all" ? "active" : "text-dark"}`
            allDatesBtn.innerHTML = `
                <span><i class="far fa-calendar mr-2"></i> All Dates</span>
                <span class="badge ${filterState.date === "all" ? "badge-light text-primary" : "badge-secondary"}">${baseForDates.length}</span>
            `
            allDatesBtn.addEventListener("click", () => {
                filterState.date = "all"
                if (dateInput) dateInput.value = ""
                updateView()
            })
            datesListEl.appendChild(allDatesBtn)

            // Individual date buttons
            sortedDates.forEach((dKey) => {
                const count = dateCounts[dKey] || 0
                if (count === 0 && filterState.date !== dKey) return

                const dDisplay = datesMap.get(dKey) || dKey
                const parts = dDisplay.split(",")
                const shortLabel = parts.length > 1 ? parts[0].substring(0, 3) + "," + parts[1] : dDisplay

                const dateBtn = doc.createElement("button")
                dateBtn.type = "button"
                dateBtn.className = `sc-date-nav-btn ${filterState.date === dKey ? "active" : "text-dark"}`
                dateBtn.innerHTML = `
                    <span class="text-truncate mr-1">${shortLabel}</span>
                    <span class="badge ${filterState.date === dKey ? "badge-light text-primary" : "badge-secondary"}">${count}</span>
                `
                dateBtn.addEventListener("click", () => {
                    filterState.date = filterState.date === dKey ? "all" : dKey
                    if (dateInput) dateInput.value = filterState.date === "all" ? "" : dKey
                    updateView()
                })
                datesListEl.appendChild(dateBtn)
            })

            // Render Slots Container
            slotsContainerEl.innerHTML = ""
            if (filtered.length === 0) {
                slotsContainerEl.innerHTML = `
                    <div class="alert alert-info text-center p-4 rounded shadow-sm bg-white border">
                        <i class="fas fa-info-circle fa-2x mb-2 text-info"></i>
                        <h6 class="font-weight-bold">No appointment slots match your current filter</h6>
                        <p class="small text-muted mb-2">Try clearing your date or location filter to view other available slots.</p>
                        <button type="button" class="btn btn-sm btn-primary sc-clear-filters-btn">
                            <i class="fas fa-undo mr-1"></i> Clear Filters
                        </button>
                    </div>
                `
                slotsContainerEl.querySelector(".sc-clear-filters-btn")?.addEventListener("click", () => {
                    filterState.location = ""
                    filterState.provider = ""
                    filterState.date = "all"
                    if (locSelect) locSelect.value = ""
                    if (provSelect) provSelect.value = ""
                    if (dateInput) dateInput.value = ""
                    updateView()
                })
                return
            }

            const visibleDates = Object.keys(grouped).sort()
            visibleDates.forEach((dKey) => {
                const dateSlots = grouped[dKey]
                const dDisplay = datesMap.get(dKey) || dKey

                const dateBlock = doc.createElement("div")
                dateBlock.className = "sc-date-block mb-4"

                const blockHeader = doc.createElement("div")
                blockHeader.className = "sc-date-block-header font-weight-bold text-dark py-2 px-3 mb-2 bg-white rounded border d-flex justify-content-between align-items-center shadow-sm"
                blockHeader.innerHTML = `
                    <span class="text-primary font-weight-bold">
                        <i class="far fa-calendar-check mr-2"></i> ${dDisplay}
                    </span>
                    <span class="badge badge-light border text-muted">${dateSlots.length} slot${dateSlots.length === 1 ? "" : "s"}</span>
                `
                dateBlock.appendChild(blockHeader)

                const grid = doc.createElement("div")
                grid.className = "sc-slots-grid"

                dateSlots.forEach((slot) => {
                    const card = doc.createElement("div")
                    card.className = "sc-slot-card shadow-sm"
                    card.innerHTML = `
                        <div class="sc-slot-time-wrap d-flex align-items-center mb-2">
                            <i class="far fa-clock text-primary mr-2 fa-lg"></i>
                            <span class="sc-slot-time">${slot.time_display}</span>
                        </div>
                        <div class="sc-slot-meta small">
                            ${slot.location_name ? `
                                <div class="text-secondary text-truncate mb-1" title="${slot.location_name}">
                                    <i class="fas fa-map-marker-alt text-danger mr-1"></i> ${slot.location_name}
                                </div>
                            ` : ""}
                            ${!fieldConfig.hide_provider && slot.provider_name ? `
                                <div class="text-secondary text-truncate" title="${slot.provider_name}">
                                    <i class="fas fa-user-md text-info mr-1"></i> ${slot.provider_name}
                                </div>
                            ` : ""}
                        </div>
                    `

                    card.addEventListener("click", async () => {
                        await this.selectSlot(slot, inputEl, fieldConfig, container, isRescheduling, closeModalFn)
                    })

                    grid.appendChild(card)
                })

                dateBlock.appendChild(grid)
                slotsContainerEl.appendChild(dateBlock)
            })
        }

        // Setup Toolbar Event Listeners
        if (locSelect) {
            locSelect.addEventListener("change", (e) => {
                filterState.location = e.target.value
                updateView()
            })
        }

        if (provSelect) {
            provSelect.addEventListener("change", (e) => {
                filterState.provider = e.target.value
                updateView()
            })
        }

        if (dateInput) {
            dateInput.addEventListener("change", (e) => {
                const picked = e.target.value
                if (!picked) {
                    filterState.date = "all"
                    updateView()
                    return
                }
                if (sortedDates.includes(picked)) {
                    filterState.date = picked
                } else {
                    const nextDate = sortedDates.find((d) => d >= picked) || sortedDates[sortedDates.length - 1]
                    if (nextDate) {
                        filterState.date = nextDate
                        dateInput.value = nextDate
                    }
                }
                updateView()
            })
        }

        resetBtn.addEventListener("click", () => {
            filterState.location = ""
            filterState.provider = ""
            filterState.date = "all"
            if (locSelect) locSelect.value = ""
            if (provSelect) provSelect.value = ""
            if (dateInput) dateInput.value = ""
            updateView()
        })

        // Initial render
        updateView()
    }

    async selectSlot(slot, inputEl, fieldConfig, container, isRescheduling, closeModalFn) {
        const saveMode = fieldConfig.save_mode
        console.log("[SchedulingCalendar JS] selectSlot:", slot, "saveMode:", saveMode)

        if (saveMode === "immediate") {
            try {
                const res = await this.callAjax({
                    action: "book-appointment",
                    pid: this.projectId,
                    record: this.record,
                    visit: fieldConfig.visit,
                    start: slot.start,
                    end: slot.end,
                    providers: slot.provider,
                    locations: slot.location,
                    timezone: "local"
                })

                if (res?.success) {
                    if (inputEl.tagName !== "TR" && "value" in inputEl) {
                        inputEl.value = slot.start
                        inputEl.dispatchEvent(new Event("change", { bubbles: true }))
                    }
                    if (closeModalFn) closeModalFn()
                    Swal.fire("Appointment Booked", `Your appointment has been scheduled for ${slot.date_display} at ${slot.time_display}.`, "success")
                    this.setupField(fieldConfig.field_name, fieldConfig)
                } else {
                    Swal.fire("Booking Failed", res?.msg || "Could not reserve selected slot.", "error")
                }
            } catch (err) {
                Swal.fire("Error", err.message || "An error occurred while booking.", "error")
            }
            return
        }

        // save_mode === "on_submit" (default)
        // 1. Set input field value if it's an actual input/textarea
        if (inputEl.tagName !== "TR" && "value" in inputEl) {
            inputEl.value = slot.start.substring(0, 16)
            inputEl.dispatchEvent(new Event("change", { bubbles: true }))
        }

        // 2. Attach hidden booking payload input to parent form
        const form = inputEl.closest("form") || doc.forms["form"] || doc.forms[0]
        if (form) {
            const hiddenInputName = `__scheduling_calendar_booking[${fieldConfig.field_name}]`
            let hiddenInput = form.querySelector(`input[name="${hiddenInputName}"]`)
            if (!hiddenInput) {
                hiddenInput = doc.createElement("input")
                hiddenInput.type = "hidden"
                hiddenInput.name = hiddenInputName
                form.appendChild(hiddenInput)
            }
            hiddenInput.value = JSON.stringify({
                visit: fieldConfig.visit,
                start: slot.start,
                end: slot.end,
                provider: slot.provider,
                location: slot.location
            })
            console.log("[SchedulingCalendar JS] Attached booking payload to form:", hiddenInputName, hiddenInput.value)
        }

        // 3. Update visible badge in container
        const badge = container.querySelector(".sc-selected-badge")
        if (badge) {
            badge.innerHTML = `
                <div class="sc-selected-datetime"><i class="fas fa-check-circle mr-1"></i> <strong>Selected:</strong> ${slot.date_display} (${slot.time_display})</div>
                ${slot.location_name ? `<div class="small mt-1" style="opacity: 0.95;"><i class="fas fa-map-marker-alt mr-1"></i> ${slot.location_name}</div>` : ""}
            `
            badge.classList.remove("d-none")
        }

        const scheduleBtn = container.querySelector(".sc-schedule-btn")
        if (scheduleBtn) {
            scheduleBtn.innerHTML = `<i class="fas fa-edit mr-1"></i> Change Selection`
        }

        if (closeModalFn) closeModalFn()
    }

    injectStyles() {
        if (doc.getElementById("sc-action-tag-injected-styles")) return
        const style = doc.createElement("style")
        style.id = "sc-action-tag-injected-styles"
        style.textContent = `
            .sc-action-tag-container { font-family: inherit; }
            .sc-loading {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                width: 100%;
                margin: auto;
                padding: 2rem 1rem;
                color: #6c757d;
                box-sizing: border-box;
            }
            .sc-loading i {
                margin-bottom: 0.5rem;
            }
            .sc-popup-wrapper {
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                width: 100%;
                max-width: 700px;
                box-sizing: border-box;
            }
            .sc-selected-badge {
                white-space: normal !important;
                word-break: normal;
                overflow-wrap: break-word;
                line-height: 1.5 !important;
                border-radius: 8px !important;
                padding: 12px 24px !important;
                font-size: 0.95rem !important;
                font-weight: 400 !important;
                box-sizing: border-box !important;
                text-align: center !important;
                max-width: 650px;
                box-shadow: 0 3px 10px rgba(40, 167, 69, 0.25) !important;
            }
            .sc-selected-badge:not(.d-none) {
                display: inline-block !important;
            }
            @media (min-width: 576px) {
                .sc-selected-datetime {
                    white-space: nowrap;
                }
            }
            .sc-appointment-card {
                border-left: 4px solid #28a745;
                border-radius: 6px;
                background-color: #f8f9fa;
                max-width: 700px;
            }
            .sc-modal-overlay {
                display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.55); z-index: 10500; align-items: center; justify-content: center;
                backdrop-filter: blur(2px);
            }
            .sc-modal-overlay.sc-modal-open { display: flex; }
            .sc-modal-dialog {
                background: #fff; width: 95%; max-width: 980px; height: 86vh; max-height: 840px; border-radius: 10px;
                box-shadow: 0 10px 35px rgba(0,0,0,0.3); display: flex; flex-direction: column; overflow: hidden;
            }
            .sc-modal-header {
                padding: 0.85rem 1.25rem; border-bottom: 1px solid #dee2e6; display: flex;
                align-items: center; justify-content: space-between; background-color: #fff;
            }
            .sc-modal-close { background: none; border: none; font-size: 1.8rem; line-height: 1; cursor: pointer; color: #6c757d; }
            .sc-modal-body { overflow: hidden; flex: 1; display: flex; flex-direction: column; width: 100%; }
            .sc-slots-content { width: 100%; flex: 1; display: flex; flex-direction: column; }
            .sc-explorer-wrapper { width: 100%; flex: 1; display: flex; flex-direction: column; }
            .sc-explorer-main { width: 100%; flex: 1; display: flex; }
            .sc-dates-sidebar { background-color: #fafbfc; }
            .sc-date-nav-btn {
                display: flex; justify-content: space-between; align-items: center; width: 100%;
                padding: 8px 12px; border-radius: 6px; border: 1px solid transparent; font-size: 0.875rem;
                font-weight: 500; text-align: left; transition: all 0.15s ease; cursor: pointer; background: transparent;
            }
            .sc-date-nav-btn:hover { background-color: #e9ecef; }
            .sc-date-nav-btn.active { background-color: #007bff; color: #fff !important; }
            .sc-date-nav-btn.active .badge { background-color: #fff; color: #007bff; }
            .sc-slots-grid {
                display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 12px;
            }
            .sc-slot-card {
                background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 14px 16px;
                transition: all 0.18s ease-in-out; cursor: pointer; display: flex; flex-direction: column;
                user-select: none;
            }
            .sc-slot-card:hover {
                border-color: #007bff; background-color: #f8fbff; box-shadow: 0 4px 14px rgba(0,123,255,0.18); transform: translateY(-2px);
            }
            .sc-slot-card:active { transform: translateY(0); }
            .sc-slot-card .sc-slot-time { font-size: 1rem; font-weight: 700; color: #212529; transition: color 0.15s ease; }
            .sc-slot-card:hover .sc-slot-time { color: #007bff; }
            .sc-slot-card.sc-slot-selected {
                border-color: #28a745 !important;
                background-color: #eafaf1 !important;
                box-shadow: 0 0 0 2px rgba(40,167,69,0.25) !important;
            }
            .sc-slot-card.sc-slot-selected .sc-slot-time {
                color: #28a745 !important;
            }
            .sc-accordion-item { overflow: hidden; transition: all 0.2s ease; }
            .sc-accordion-header { transition: background-color 0.15s ease; outline: none !important; }
            .sc-accordion-header:hover { background-color: #f8f9fa !important; }
            .sc-accordion-chevron { transition: transform 0.2s ease; }
            .sc-accordion-open .sc-accordion-chevron { transform: rotate(180deg); }
            .sc-accordion-open .sc-accordion-header { border-bottom-left-radius: 0 !important; border-bottom-right-radius: 0 !important; }
            .gap-1 { gap: 0.25rem; }
            .gap-2 { gap: 0.5rem; }
            .gap-3 { gap: 1rem; }
            @media (max-width: 768px) {
                .sc-modal-dialog { width: 98%; height: 94vh; max-height: 94vh; }
                .sc-explorer-main { flex-direction: column !important; }
                .sc-dates-sidebar {
                    width: 100% !important; max-height: 85px; flex-direction: row !important;
                    overflow-x: auto !important; overflow-y: hidden !important; border-right: none !important;
                    border-bottom: 1px solid #dee2e6;
                }
                .sc-dates-list { flex-direction: row !important; }
                .sc-date-nav-btn { width: auto !important; white-space: nowrap; }
                .sc-slots-grid { grid-template-columns: 1fr; }
            }
        `
        doc.head.appendChild(style)
    }
}

const initActionTag = () => {
    console.log("[SchedulingCalendar JS] initActionTag executing. readyState:", doc.readyState)
    const config = window.ExternalModules?.UWMadison?.Scheduling?.actionTagConfig || window.actionTagConfig
    console.log("[SchedulingCalendar JS] Discovered actionTagConfig:", config)
    if (config && !window.__scActionTagInitialized) {
        window.__scActionTagInitialized = true
        window.schedulerWidget = new ActionTagScheduler(config)
    }
}

if (doc.readyState === "loading") {
    doc.addEventListener("DOMContentLoaded", initActionTag)
} else {
    initActionTag()
}

export default ActionTagScheduler
