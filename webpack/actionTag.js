import Swal from "sweetalert2-optimized"
import RedCap from "./redcap"
import "./actiontag.less"
import tplNoSlots from "./html/actiontag_no_slots.html"
import tplNoFilterMatch from "./html/actiontag_no_filter_match.html"
import tplSidebar from "./html/actiontag_sidebar.html"
import tplResetBtn from "./html/actiontag_reset_btn.html"
import tplLoading from "./html/actiontag_loading.html"
import tplModal from "./html/actiontag_modal.html"
import tplSlotsPanel from "./html/actiontag_slots_panel.html"
import tplApptCard from "./html/actiontag_appointment_card.html"
import tplSlotCard from "./html/actiontag_slot_card.html"
import tplSelectedBadge from "./html/actiontag_selected_badge.html"
import tplPopup from "./html/actiontag_popup.html"
import tplInline from "./html/actiontag_inline.html"

class ActionTagScheduler {
    static renderLoading(message = "Loading available appointment times...", iconClass = "fa-2x") {
        return RedCap.ttHTML(tplLoading, {
            SPINNER_CLASS: iconClass,
            MESSAGE: message
        })
    }

    static renderAppointmentCard(appointment, fieldConfig) {
        const locationBlock = appointment.location_name ? `<div><strong>${RedCap.tt("html_location") || "Location"}:</strong> ${appointment.location_name}</div>` : ""
        const providerBlock = !fieldConfig.hide_provider && appointment.provider_name ? `<div><strong>${RedCap.tt("html_provider") || "Provider"}:</strong> ${appointment.provider_name}</div>` : ""
        const rescheduleBtn = fieldConfig.allow_reschedule ? `<button type="button" class="btn btn-sm btn-outline-primary sc-reschedule-btn mb-1"><i class="fas fa-edit"></i> ${RedCap.tt("html_reschedule") || "Reschedule"}</button>` : ""
        const cancelBtn = fieldConfig.allow_cancel ? `<button type="button" class="btn btn-sm btn-outline-danger sc-cancel-btn"><i class="fas fa-times"></i> ${RedCap.tt("html_cancel") || "Cancel"}</button>` : ""

        return RedCap.ttHTML(tplApptCard, {
            DATE_DISPLAY: appointment.date_display,
            TIME_DISPLAY: appointment.time_display,
            LOCATION_BLOCK: locationBlock,
            PROVIDER_BLOCK: providerBlock,
            RESCHEDULE_BTN: rescheduleBtn,
            CANCEL_BTN: cancelBtn
        })
    }

    static renderModalDialog(title = "Select Appointment Time") {
        const loadingHtml = ActionTagScheduler.renderLoading("Loading available appointment times...", "fa-3x")
        return RedCap.ttHTML(tplModal, {
            TITLE: title,
            CONTENT: loadingHtml
        })
    }

    static renderSlotCard(slot, fieldConfig) {
        const locationBlock = slot.location_name ? `
            <div class="text-secondary text-truncate mb-1" title="${slot.location_name}">
                <i class="fas fa-map-marker-alt text-danger mr-1"></i> ${slot.location_name}
            </div>
        ` : ""
        const providerBlock = !fieldConfig.hide_provider && slot.provider_name ? `
            <div class="text-secondary text-truncate" title="${slot.provider_name}">
                <i class="fas fa-user-md text-info mr-1"></i> ${slot.provider_name}
            </div>
        ` : ""

        return RedCap.ttHTML(tplSlotCard, {
            TIME_DISPLAY: slot.time_display,
            LOCATION_BLOCK: locationBlock,
            PROVIDER_BLOCK: providerBlock
        })
    }

    static renderSlotsPanel(timezone) {
        const tzDisplay = timezone === "local" ? (RedCap.tt("html_server_time") || "Server Time") : timezone
        return RedCap.ttHTML(tplSlotsPanel, {
            TIMEZONE: tzDisplay
        })
    }

    static renderSelectedBadge(slot) {
        const locationBlock = slot.location_name ? `<div class="small mt-1" style="opacity: 0.95;"><i class="fas fa-map-marker-alt mr-1"></i> ${slot.location_name}</div>` : ""
        return RedCap.ttHTML(tplSelectedBadge, {
            DATE_DISPLAY: slot.date_display,
            TIME_DISPLAY: slot.time_display,
            LOCATION_BLOCK: locationBlock
        })
    }

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
        for (const [fieldName, fieldConfig] of Object.entries(this.fields))
            await this.setupField(fieldName, fieldConfig)
    }

    async callAjax(payload) {
        console.log("[SchedulingCalendar JS] callAjax sending payload:", payload)
        const res = await RedCap.ajax("survey-calendar-api", payload)
        console.log("[SchedulingCalendar JS] callAjax received response:", res)
        return res
    }

    findFieldInput(fieldName) {
        return $.querySelector(`input[name="${fieldName}"]`) ||
            $.querySelector(`textarea[name="${fieldName}"]`) ||
            $.getElementById(`${fieldName}-tr`) ||
            $.querySelector(`tr#${fieldName}-tr`) ||
            $.querySelector(`tr[sq_id="${fieldName}"]`) ||
            $.querySelector(`[data-rc-field="${fieldName}"]`)
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
        let container = $.createElement("div")
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
        container.innerHTML = ActionTagScheduler.renderLoading("Checking appointment status...", "fa-2x")

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
            const el = $.querySelector(`[name="${fieldConfig.piped_location}"]`)
            if (el) el.addEventListener("change", checkAndReload)
        }
        if (fieldConfig.piped_provider) {
            const el = $.querySelector(`[name="${fieldConfig.piped_provider}"]`)
            if (el) el.addEventListener("change", checkAndReload)
        }
    }

    getPipedValue(param, pipedParam) {
        if (pipedParam) {
            const el = $.querySelector(`[name="${pipedParam}"]`)
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
            const wrapper = $.createElement("div")
            wrapper.className = "d-flex justify-content-center my-2"

            const card = $.createElement("div")
            card.className = "sc-appointment-card alert alert-info p-3 mb-0 shadow-sm"
            card.style.maxWidth = "700px"
            card.style.width = "100%"
            card.innerHTML = ActionTagScheduler.renderAppointmentCard(appointment, fieldConfig)

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
            const prompt = isRescheduling ? (RedCap.tt("html_reschedule_prompt") || "Reschedule your appointment:") : (RedCap.tt("html_schedule_prompt") || "Schedule an appointment:")
            const btnText = isRescheduling ? (RedCap.tt("html_choose_new_time") || "Choose New Date & Time") : fieldConfig.btn_text
            const cancelReschedBtn = isRescheduling && oldAppt ? `
                <button type="button" class="btn btn-sm btn-outline-secondary mt-3 sc-keep-current-btn">
                    <i class="fas fa-undo mr-1"></i> ${RedCap.tt("html_keep_current_appt") || "Keep Current Appointment"}
                </button>
            ` : ""

            const wrapper = $.createElement("div")
            wrapper.innerHTML = RedCap.ttHTML(tplPopup, {
                PROMPT: prompt,
                BTN_TEXT: btnText,
                CANCEL_RESCHEDULE_BTN: cancelReschedBtn
            })
            const btnWrap = wrapper.firstElementChild
            btnWrap.querySelector(".sc-schedule-btn")?.addEventListener("click", () => this.openModal(container, inputEl, fieldConfig, isRescheduling, oldAppt))
            btnWrap.querySelector(".sc-keep-current-btn")?.addEventListener("click", () => {
                this.renderWidget(container, inputEl, fieldConfig, oldAppt)
            })

            container.appendChild(btnWrap)
        } else {
            // inline mode - direct elements without nested card wrapper
            const cancelReschedBlock = isRescheduling && oldAppt ? `
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary sc-cancel-resched">
                        <i class="fas fa-undo mr-1"></i> ${RedCap.tt("html_keep_current_appt") || "Keep Current Appointment"}
                    </button>
                </div>
            ` : ""

            const wrapper = $.createElement("div")
            wrapper.innerHTML = RedCap.ttHTML(tplInline, {
                CANCEL_RESCHEDULE_BLOCK: cancelReschedBlock,
                LOADING: ActionTagScheduler.renderLoading("Loading available slots...")
            })
            const inlineWrap = wrapper.firstElementChild

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
        let modalOverlay = $.getElementById("sc-modal-overlay")
        if (!modalOverlay) {
            modalOverlay = $.createElement("div")
            modalOverlay.id = "sc-modal-overlay"
            modalOverlay.className = "sc-modal-overlay"
            modalOverlay.innerHTML = ActionTagScheduler.renderModalDialog(fieldConfig.field_label || "Select Appointment Time")
            $.body.appendChild(modalOverlay)

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
        slotsContent.innerHTML = ActionTagScheduler.renderLoading("Loading available appointment times...", "fa-3x")

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
                targetElement.innerHTML = RedCap.ttHTML(tplNoSlots)
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

        const accordionWrapper = $.createElement("div")
        accordionWrapper.className = "sc-inline-accordion"

        const subHeader = $.createElement("div")
        subHeader.className = "d-flex justify-content-between align-items-center mb-3 text-muted small"
        subHeader.innerHTML = `
            <span><i class="far fa-calendar-check mr-1"></i> ${sortedDates.length} date${sortedDates.length === 1 ? "" : "s"} available</span>
            <span><i class="fas fa-clock mr-1"></i> ${timezone === "local" ? "Server Time" : timezone}</span>
        `
        accordionWrapper.appendChild(subHeader)

        const groupContainer = $.createElement("div")
        groupContainer.className = "sc-accordion-group d-flex flex-column gap-2"

        const itemElements = new Map()

        sortedDates.forEach((dKey) => {
            const dateSlots = grouped[dKey]
            const dDisplay = datesMap.get(dKey) || dKey

            const item = $.createElement("div")
            item.className = `sc-accordion-item border rounded bg-white shadow-sm ${activeDate === dKey ? "sc-accordion-open" : ""}`
            item.dataset.date = dKey

            const header = $.createElement("button")
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

            const body = $.createElement("div")
            body.className = `sc-accordion-body p-3 bg-light border-top ${activeDate === dKey ? "" : "d-none"}`

            const grid = $.createElement("div")
            grid.className = "sc-slots-grid"

            dateSlots.forEach((slot) => {
                const card = $.createElement("div")
                card.className = "sc-slot-card shadow-sm"
                card.dataset.slotStart = slot.start
                card.innerHTML = ActionTagScheduler.renderSlotCard(slot, fieldConfig)

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

        const explorerWrapper = $.createElement("div")
        explorerWrapper.className = "sc-explorer-wrapper d-flex flex-column flex-grow-1 h-100 w-100"
        explorerWrapper.style.width = "100%"

        // 1. Toolbar
        const toolbar = $.createElement("div")
        toolbar.className = "sc-explorer-toolbar px-3 py-2 bg-light border-bottom d-flex flex-wrap align-items-center gap-3"

        // Location filter
        let locSelect = null
        if (locations.size > 1) {
            const locGroup = $.createElement("div")
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
            const locItem = $.createElement("div")
            locItem.className = "sc-filter-group small text-muted d-flex align-items-center"
            locItem.innerHTML = `<i class="fas fa-map-marker-alt text-danger mr-1"></i> <strong>Location:</strong>&nbsp;${Array.from(locations.values())[0]}`
            toolbar.appendChild(locItem)
        }

        // Provider filter
        let provSelect = null
        if (!fieldConfig.hide_provider && providers.size > 1) {
            const provGroup = $.createElement("div")
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
        const datePickerGroup = $.createElement("div")
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
        const summaryGroup = $.createElement("div")
        summaryGroup.className = "ml-auto d-flex align-items-center"
        summaryGroup.innerHTML = RedCap.ttHTML(tplResetBtn)
        const resetBtn = summaryGroup.querySelector(".sc-reset-btn")
        toolbar.appendChild(summaryGroup)

        explorerWrapper.appendChild(toolbar)

        // 2. Main 2-Column Split
        const mainSplit = $.createElement("div")
        mainSplit.className = "sc-explorer-main d-flex flex-grow-1 w-100"
        mainSplit.style.minHeight = "0"
        mainSplit.style.overflow = "hidden"
        mainSplit.style.width = "100%"

        // Left Sidebar: Dates List
        const sidebar = $.createElement("div")
        sidebar.className = "sc-dates-sidebar border-right bg-white p-2 d-flex flex-column"
        sidebar.style.width = "250px"
        sidebar.style.flexShrink = "0"
        sidebar.style.overflowY = "auto"
        sidebar.innerHTML = RedCap.ttHTML(tplSidebar)
        const datesListEl = sidebar.querySelector(".sc-dates-list")
        mainSplit.appendChild(sidebar)

        // Right Content: Slots Panel
        const slotsPanel = $.createElement("div")
        slotsPanel.className = "sc-slots-panel flex-grow-1 p-3 bg-light d-flex flex-column"
        slotsPanel.style.overflowY = "auto"
        slotsPanel.innerHTML = ActionTagScheduler.renderSlotsPanel(timezone)
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
            const allDatesBtn = $.createElement("button")
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

                const dateBtn = $.createElement("button")
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
                slotsContainerEl.innerHTML = RedCap.ttHTML(tplNoFilterMatch)
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

                const dateBlock = $.createElement("div")
                dateBlock.className = "sc-date-block mb-4"

                const blockHeader = $.createElement("div")
                blockHeader.className = "sc-date-block-header font-weight-bold text-dark py-2 px-3 mb-2 bg-white rounded border d-flex justify-content-between align-items-center shadow-sm"
                blockHeader.innerHTML = `
                    <span class="text-primary font-weight-bold">
                        <i class="far fa-calendar-check mr-2"></i> ${dDisplay}
                    </span>
                    <span class="badge badge-light border text-muted">${dateSlots.length} slot${dateSlots.length === 1 ? "" : "s"}</span>
                `
                dateBlock.appendChild(blockHeader)

                const grid = $.createElement("div")
                grid.className = "sc-slots-grid"

                dateSlots.forEach((slot) => {
                    const card = $.createElement("div")
                    card.className = "sc-slot-card shadow-sm"
                    card.innerHTML = ActionTagScheduler.renderSlotCard(slot, fieldConfig)

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
        const form = inputEl.closest("form") || $.forms["form"] || $.forms[0]
        if (form) {
            const hiddenInputName = `__scheduling_calendar_booking[${fieldConfig.field_name}]`
            let hiddenInput = form.querySelector(`input[name="${hiddenInputName}"]`)
            if (!hiddenInput) {
                hiddenInput = $.createElement("input")
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
            badge.innerHTML = ActionTagScheduler.renderSelectedBadge(slot)
            badge.classList.remove("d-none")
        }

        const scheduleBtn = container.querySelector(".sc-schedule-btn")
        if (scheduleBtn) {
            scheduleBtn.innerHTML = `<i class="fas fa-edit mr-1"></i> Change Selection`
        }

        if (closeModalFn) closeModalFn()
    }
}

const initActionTag = () => {
    console.log("[SchedulingCalendar JS] initActionTag executing. readyState:", $.readyState)
    const config = RedCap.module.actionTagConfig
    console.log("[SchedulingCalendar JS] Discovered actionTagConfig:", config)
    if (config && !window.__scActionTagInitialized) {
        window.__scActionTagInitialized = true
        window.schedulerWidget = new ActionTagScheduler(config)
    }
}

if ($.readyState === "loading") {
    $.addEventListener("DOMContentLoaded", initActionTag)
} else {
    initActionTag()
}

export default ActionTagScheduler
