<?php
$HtmlPage = new HtmlPage();
$HtmlPage->addStylesheet("home.css", 'screen,print');
$HtmlPage->PrintHeader();
include APP_PATH_VIEWS . 'HomeTabs.php';
?>
<style>
    .container a {
        text-decoration: underline;
        color: #0056b3;
        font-weight: 500;
    }

    .container a:hover {
        color: #003d82;
    }

    #toc a {
        text-decoration: none;
        color: #212529;
        padding: 0.25rem 0;
        display: block;
        border-radius: 0.25rem;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    #toc a:hover {
        background-color: #e9ecef;
        color: #0056b3;
        text-decoration: none;
        padding-left: 0.5rem;
    }

    .alert-warning {
        background-color: #fff3cd;
        border-color: #ffeaa7;
        border-left: 4px solid #f39c12;
        color: #856404;
    }

    .alert-warning .fa-triangle-exclamation {
        color: #f39c12;
    }

    .alert-warning a {
        color: #704214;
        font-weight: 600;
    }
</style>
<div class="projhdr">
    <h3><i class="fas fa-calendar"></i> Scheduling & Availability Documentation</h3>
</div>

<div class="bg-white container m-0 p-0">
    <div class="row">
        <div class="col-10">
            <div class="alert alert-warning my-4">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="fa-solid fa-triangle-exclamation fa-2xl"></i>
                    </div>
                    <div class="col">
                        <span class="font-weight-bold">This docs page is still a major work in progress. A lot of information is missing, poorly formatted, or just too vague. If you have questions you can reach out to the author at <a href="mailto:adam.nunez@ctri.wisc.edu">adam.nunez@ctri.wisc.edu</a>.</span>
                    </div>
                </div>
            </div>
            <div id="purpose" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Purpose</div>
                <div class="card-body">
                    This external module adds comprehensive scheduling and availability management functionality to the REDCap platform.
                    It allows providers (logged-in users) to set their availability across projects and enables clinical or study staff
                    to schedule appointments for subjects against that availability seamlessly within REDCap.
                    <br><br>
                    The goal of this module is to eliminate reliance on external calendar software or third-party scheduling tools.
                    While REDCap includes a native Calendar module for basic project needs, this external module is designed for larger teams,
                    complex studies with multi-site locations, shared provider groups across multiple REDCap projects, time-zone sensitive scheduling,
                    and automated data writeback into REDCap instruments.
                    <br><br>
                    Please review the setup instructions and configuration sections below. If you encounter any configuration issues or have feature requests,
                    contact the developers via <a href="mailto:adam.nunez@ctri.wisc.edu">email</a> or open a <a href="https://github.com/uwctri/SchedulingCalendar/issues/new">GitHub issue</a>.
                </div>
            </div>
            <div id="workflow" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Workflow & Navigation</div>
                <div class="card-body">
                    The module's workflow is divided into three primary navigation views available in the top right menu of the calendar:
                    <br><br>
                    <p><b>1. Edit Availability (<code>type=edit</code>)</b><br>
                        Providers or Calendar Admins use this view to set available working hours on the calendar.
                        Availability can be entered manually by selecting time blocks directly on the calendar or using the <strong>Bulk Edit</strong> tool to generate recurring weekly schedules.
                        Exceptions (e.g., holidays, vacations, or sick leave) can be modified or removed at any time. Calendar Admins can manage availability on behalf of any provider.</p>

                    <p><b>2. Schedule Appointments (<code>type=schedule</code>)</b><br>
                        Staff members use this view to schedule subject appointments against posted provider availability.
                        Users can filter by provider, subject name/ID, visit type, or clinic location. The module enforces visit duration, checks location constraints,
                        evaluates branching logic, and prevents double-booking. When scheduled, the appointment automatically populates designated writeback fields on the subject's REDCap record.</p>

                    <p><b>3. My Calendar (<code>type=my</code>)</b><br>
                        Providers use this personal view to see all upcoming appointments assigned to them across all linked projects in one consolidated calendar, along with direct links to the relevant REDCap subject records.</p>
                </div>
            </div>
            <div id="actiontag" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient d-flex justify-content-between align-items-center">
                    <span>Action Tag Integration</span>
                    <span class="badge bg-warning text-dark font-weight-bold px-2 py-1">Coming Soon</span>
                </div>
                <div class="card-body">
                    The module supports the <code>@SCHEDULING-CALENDAR</code> action tag to embed an interactive scheduling calendar directly into data entry forms or survey instruments.
                    <br><br>
                    <h5 class="text-decoration-underline">Action Tag Syntax</h5>
                    <pre>
                        <code>
                            @SCHEDULING-CALENDAR([start_date], [end_date], "visit code or linked [event_name]", time_in_minutes, [provider], [location], "popup-or-inline")
                        </code>
                    </pre>
                    <p><b>Parameters:</b><br>
                        1. <b>start_date</b> – Optional start date for scheduling range (can be a static string like <code>"2026-08-01"</code> or a piped field <code>[baseline_date]</code>).<br>
                        2. <b>end_date</b> – Optional end date for scheduling range (static string or piped field <code>[visit_window_end]</code>).<br>
                        3. <b>visit_code</b> – The visit code or linked event name configured in project settings.<br>
                        4. <b>time_in_minutes</b> – Visit duration override in minutes (e.g., <code>30</code> or <code>60</code>).<br>
                        5. <b>provider</b> – Optional pre-selected provider username or piped field.<br>
                        6. <b>location</b> – Optional pre-selected location code or piped field.<br>
                        7. <b>mode</b> – Rendering mode: <code>"popup"</code> (launches modal) or <code>"inline"</code> (embeds inside form).
                    </p>
                    <p>All parameter positions support REDCap field piping as well as static string values. This allows dynamic scheduling workflows based on participant data entered earlier in a survey or instrument.</p>
                </div>
            </div>
            <div id="config" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Project Configuration</div>
                <div class="card-body">
                    Due to the comprehensive nature of the module, it is strongly advised to restrict access to the External Modules configuration page to authorized project administrators.
                    <br><br>
                    <h5 class="text-decoration-underline">General Configuration</h5>
                    <p><b>Calendar Admin</b><br>
                        Users assigned as Calendar Admins may edit any provider's availability, modify existing appointments on behalf of others, and access administrative tools:<br>
                        1. <i>Data Clean Up</i> – Purge past availability and cancel appointments for withdrawn subjects.<br>
                        2. <i>ICS Export & Live Feed</i> – Generate calendar files or secure subscription links for calendar integration.<br>
                        3. <i>User Color Configuration</i> – Assign custom hex colors for providers.<br>
                    </p>
                    <p><b>Unschedulable Users</b><br>
                        List of users who possess project access and historical availability records but should no longer be listed as schedulable providers in dropdowns or search filters.</p>
                    <p><b>Trigger DET</b><br>
                        Sends an HTTP POST payload to the project's Data Entry Trigger (DET) endpoint whenever availability or appointments are added, modified, or deleted. See the DET section below for payload details.</p>
                    <p><b>Name Field</b><br>
                        REDCap variable name used to index, search, and display subject names on the calendar and subject summary panel (e.g., <code>full_name</code> or <code>first_name</code>).</p>
                    <p><b>Withdraw Flag</b><br>
                        Field indicating that a subject has withdrawn from the study. When set to any non-blank, non-zero value, the subject is flagged as withdrawn and excluded from search results for new appointments.</p>
                    <p><b>Default Location</b><br>
                        Configures how appointment locations are assigned when scheduling:<br>
                        • <strong>Blank</strong> – Provider selects location manually during scheduling.<br>
                        • <strong>Field</strong> – Auto-selects location based on a field value in the subject's record.<br>
                        • <strong>Static</strong> – Auto-selects a static location code for all appointments.
                    </p>
                    <p><b>Location Source</b><br>
                        Defines where the clinic/location structure is loaded from:<br>
                        • <strong>Field</strong> – Uses select choices from a REDCap field dictionary.<br>
                        • <strong>Local JSON</strong> – Uses a JSON structure stored in the project's settings.<br>
                        • <strong>Another Project</strong> – Pulls the location JSON dynamically from another REDCap project ID, establishing a single source of truth across studies.
                    </p>
                    <p><b>Timezones</b><br>
                        Newline-separated list of valid <code>tzDatabase</code> timezone identifiers and display aliases (e.g., <code>America/Chicago, Central</code> or <code>America/New_York, Eastern</code>).
                        When multiple timezones are configured, a timezone selector appears on the calendar header, and all appointment & availability times dynamically render in the selected timezone.
                    </p>
                    <h5 class="text-decoration-underline">Schedulable Visit Configuration</h5>
                    <p>Repeatable configuration block for defining visit types available for scheduling on the calendar:</p>
                    <p><b>Display Name</b> – Human-readable label displayed in dropdowns and on calendar events.</p>
                    <p><b>Internal Coded Value</b> – Unique alphanumeric code identifying the visit type.</p>
                    <p><b>Linked Event</b> – REDCap event associated with this visit type for data writeback.</p>
                    <p><b>Notes</b> – Optional instructions or notes displayed on the subject summary card when this visit is selected.</p>
                    <p><b>Branching Logic</b> – REDCap field, event, and value logic determining whether this visit type is available for scheduling for a given subject.</p>
                    <p><b>Duration</b> – Default length of the appointment in minutes.</p>
                    <p><b>Allow Additional Time</b> – When enabled, the duration becomes a minimum length, allowing providers to extend appointment times during scheduling.</p>
                    <p><b>Allow Any Location</b> – Bypasses clinic location checks for virtual, phone, or location-independent visits.</p>
                    <h5 class="text-decoration-underline">Shared Schedulable Visit Config</h5>
                    <p><b>Date/Time Writeback</b> – Field(s) on the linked event automatically populated with the scheduled date and/or time upon booking (supports date, time, and datetime validation types).</p>
                    <p><b>Provider Writeback</b> – Field on the linked event automatically populated with the scheduled provider's username.</p>
                    <p><b>Visit Range Start / End</b> – Fields indicating the recommended or valid window for scheduling the visit, displayed as a recommended range on the subject summary.</p>
                    <h5 class="text-decoration-underline">Subject Summary & ICS Export Extra Fields</h5>
                    <p><b>Additional Fields</b> – Select extra REDCap fields (e.g., phone, email, secondary contact) to display on the right-side subject summary panel or include in ICS calendar exports.</p>
                </div>
            </div>
            <div id="sys" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">System Configuration</div>
                <div class="card-body">
                    System-level settings are configured by REDCap System Administrators in the Control Center under External Modules configuration.
                    <br><br>
                    <p><b>Availability Groups</b><br>
                        Defines availability group codes and display names available system-wide or shared across specific projects.<br>
                        • <strong>Group Name</strong> – Display label for the group.<br>
                        • <strong>Group Code</strong> – Coded value assigned to provider availability blocks.</p>
                    <p><b>Project Availability Group</b><br>
                        Maps availability group codes to specific REDCap project IDs, enabling provider availability to be shared across multiple studies.</p>
                    <p><b>Allow Global Group</b><br>
                        Enables a shared "Global Group" accessible to all projects on the REDCap instance.</p>
                    <p><b>Prevent Local Group</b><br>
                        Disables the default project-local availability group, forcing projects to use configured system availability groups.</p>
                </div>
            </div>
            <div id="admin" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Admin Tools</div>
                <div class="card-body">
                    Users designated as Calendar Admins can access administrative utility tools via the toolbar icon in the bottom-left corner of the calendar page:
                    <br><br>
                    <p><b>1. Clean Up Tool</b><br>
                        Allows administrators to bulk-purge historical availability entries prior to a selected date or automatically cancel appointments for subjects flagged as withdrawn.</p>
                    <p><b>2. ICS Export & Secure Live Feed</b><br>
                        Generates an iCalendar (<code>.ics</code>) file containing upcoming appointments for import into Outlook, Apple Calendar, or Google Calendar.
                        Admins can also copy a unique secure feed URL to subscribe to calendar updates automatically.</p>
                    <p><b>3. User Color Configuration</b><br>
                        Assigns fixed custom hex colors for providers on the calendar interface, overriding default random/accessible palette colors.</p>
                </div>
            </div>
            <div id="locs" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Location Settings Structure</div>
                <div class="card-body">
                    Locations are structured as a JSON object containing coded values, display names, active status, and optional sub-location hierarchies.
                    Below are two reference examples:
                    <div class="container">
                        <div class="row">
                            <div class="col-6">
                                <pre>
                                <code>
                                    {
                                        "WFH": {
                                            "name": "Work From Home / Virtual",
                                            "active": true,
                                            "in_person": false
                                        },
                                        "CTRIMAD": {
                                            "name": "Madison Clinic",
                                            "active": true,
                                            "sub": {
                                                "CMADE": {
                                                    "name": "Madison East Wing",
                                                    "active": true
                                                },
                                                "CMADW": {
                                                    "name": "Madison West Wing",
                                                    "active": true
                                                }
                                            }
                                        },
                                        "CTRIMKE": {
                                            "name": "Milwaukee Clinic",
                                            "active": true
                                        }
                                    }
                                </code>
                                </pre>
                                <p>Multi-location setup with sub-clinic wings and a virtual/phone option.</p>
                            </div>
                            <div class="col-6">
                                <pre>
                                <code>
                                    {
                                        "call": {
                                            "name": "Phone Call",
                                            "active": true,
                                            "in_person": false
                                        },
                                        "site": {
                                            "name": "Main Office",
                                            "active": true,
                                            "in_person": true
                                        }
                                    }
                                </code>
                                </pre>
                                <p>Simple 2-location setup distinguishing phone/virtual visits from in-person office visits.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="timezones" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Timezone Configuration & Behavior</div>
                <div class="card-body">
                    The module provides full multi-timezone support for studies operating across multiple geographic regions or timezones.
                    <br><br>
                    <p><b>Storage Standard:</b> All event and availability timestamps (<code>time_start</code>, <code>time_end</code>) are stored in the server's local timezone in the database.</p>
                    <p><b>Dynamic Presentation:</b> When timezones are configured in Project Settings, a timezone dropdown appears on the top header bar of the calendar.
                        When a user selects a timezone, all availability slots, scheduled appointments, popups, and subject summary visit times dynamically adjust to render in that selected timezone.</p>
                    <p><b>Subject Summary Disclaimer:</b> When multiple timezones are configured, the subject summary card header displays a dynamic notice (e.g., <i>"Times below are in Central"</i>) clarifying the active timezone for displayed visits.</p>
                </div>
            </div>
            <div id="det" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">DET Integration</div>
                <div class="card-body">
                    When <b>Trigger DET</b> is enabled in Project Settings, the module dispatches an HTTP POST payload to the project's Data Entry Trigger URL whenever availability or appointments are created, updated, or deleted.
                    <br><br>
                    <h5 class="text-decoration-underline">DET HTTP POST Payload Schema</h5>
                    <pre>
                        <code>
                            {
                                redcap_url: "Root URL of REDCap instance",
                                project_url: "URL to project index.php?pid=[project-id]",
                                project_id: "Numeric Project ID",
                                username: "Username of user performing action",
                                resource: "Resource type (Availability or Appointment)",
                                crud: "CRUD operation (create, update, delete)",
                                msg: "Status or operation description message",
                                ___
                                // Sent when resource = Appointment and crud = create:
                                start: "Start datetime in ISO/SQL format",
                                end: "End datetime in ISO/SQL format",
                                providers: ["provider_username"],
                                locations: ["location_code"],
                                subjects: ["record_id"],
                                visits: ["visit_code"],
                                notes: "Appointment notes if any",
                                ___
                                // Sent when resource = Availability and crud = create:
                                start: "Start datetime in ISO/SQL format",
                                end: "End datetime in ISO/SQL format",
                                providers: ["provider_username"],
                                locations: ["location_code"],
                                group: "Availability group code",
                                ___
                                // Sent when crud = update or delete:
                                id: "Internal database ID of calendar entry",
                                data: "Full associative array of deleted or updated row"
                            }
                        </code>
                    </pre>
                    <h5 class="text-decoration-underline">Direct Database Query Example (PHP)</h5>
                    <p>If your custom DET endpoint runs on the same server, you can query the database entry directly using the internal ID:</p>
                    <pre>
                        <code>
                            define("NOAUTH", true);
                            require_once "../redcap_connect.php";
                            $sql = "SELECT * FROM em_scheduling_calendar WHERE id = ?";
                            $result = db_query($sql, [$_POST["id"]]);
                            $data = db_fetch_assoc($result);
                            // $data contains: project_id, visit, availability_code, user, record, location, time_start, time_end, notes
                        </code>
                    </pre>
                </div>
            </div>
            <div id="query" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">URL Query Parameters & Deep Linking</div>
                <div class="card-body">
                    You can construct deep links to the calendar from REDCap data entry forms, survey instruments, or external tools using URL query parameters.
                    <br><br>
                    <table class="table table-striped table-hover my-3">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">Parameter</th>
                                <th scope="col">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="nowrap"><b>record</b> or <b>id</b></td>
                                <td>Pre-selects a subject record ID on page load. Useful for embedding scheduling links inside instruments.</td>
                            </tr>
                            <tr>
                                <td><b>date</b></td>
                                <td>Sets initial calendar view date (format <code>YYYY-MM-DD</code>). Defaults to current date.</td>
                            </tr>
                            <tr>
                                <td><b>type</b></td>
                                <td>Initial page view mode: <code>schedule</code> (default), <code>edit</code> (availability), or <code>my</code> (my calendar).</td>
                            </tr>
                            <tr>
                                <td><b>tz</b></td>
                                <td>Pre-selects an active timezone identifier (e.g., <code>tz=America/Chicago</code>).</td>
                            </tr>
                            <tr>
                                <td><b>refer</b></td>
                                <td>Set to <code>true</code> or an <code>encodeURIComponent()</code> URL string. Renders a "Return to Workflow" button in the bottom right corner.</td>
                            </tr>
                        </tbody>
                    </table>

                    <h5 class="text-decoration-underline">Deep Linking Examples</h5>
                    <p>Basic link to open calendar for current subject:</p>
                    <pre>
                        <code>
                            [redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]&record=[record-name]
                        </code>
                    </pre>

                    <p>Link passing piped target date and pre-selected subject:</p>
                    <pre>
                        <code>
                            [redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]&record=[record-name]&date=[target_date_field]
                        </code>
                    </pre>

                    <p>JavaScript link with encoded return URL (e.g., in Shazam EM or descriptive field):</p>
                    <pre>
                        <code>
                            let refer = encodeURIComponent(window.location.href);
                            let url = `${redcap_version}/ExternalModules/?prefix=scheduling_calendar&page=index&pid=${pid}&record=${record}&refer=${refer}`;
                            url = location.href.split(redcap_version)[0] + url;
                        </code>
                    </pre>
                </div>
            </div>
        </div>
        <div class="sticky-top col-2 h-100 pt-4" style="top:4em">
            <div class="card shadow-sm">
                <div class="card-body p-2">
                    <nav id="toc"></nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Format code blocks
    $("#pagecontainer pre").each((_, el) => {
        let t = $(el).text().split('\n').filter((line) => line.trim().length > 0).map((line) => line.replace("___", ""))
        let l = t[0].search(/\S/)
        $(el).text(t.map((line) => line.slice(l)).join('\n'))
    })

    // Generate TOC
    $(".container [id]").each((_, el) => {
        let id = $(el).attr('id')
        if (id == 'toc') return
        let text = $(el).find('.card-header').text()
        $("#toc").append(`<div><a href="#${id}">${text}</a></div>`)
    })
</script>

<style>
    #pagecontainer {
        max-width: 1400px;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        line-height: 1.6;
        color: #212529;
    }

    .card {
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        scroll-margin-top: 4em;
        margin-bottom: 2rem;
    }

    .card-header {
        font-size: 1.25em;
        font-weight: 600;
        border-bottom: 2px solid rgba(255, 255, 255, 0.3);
    }

    .card-body {
        font-size: 1.05em;
        color: #212529;
        padding: 2rem;
        line-height: 1.65;
    }

    .card-body h5 {
        color: #1a252f;
        margin-top: 2rem;
        margin-bottom: 1rem;
        font-weight: 700;
        font-size: 1.15em;
    }

    .card-body p {
        margin-bottom: 1.5rem;
        text-align: justify;
        color: #2c3e50;
    }

    .card-body p b {
        color: #1a252f;
        font-weight: 700;
    }

    #toc {
        font-size: 0.95em;
    }

    pre {
        background-color: #f1f3f4;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 1.25rem;
        margin: 1.5rem 0;
        overflow-x: auto;
        font-size: 0.875em;
        line-height: 1.5;
        color: #1f2937;
    }

    pre code {
        background: none;
        border: none;
        padding: 0;
        color: #1f2937;
        font-weight: 500;
    }

    .table {
        font-size: 0.95em;
        color: #212529;
    }

    .table th {
        font-weight: 700;
        border-top: none;
        color: #ffffff;
        background-color: #343a40;
    }

    .table td {
        color: #2c3e50;
        border-color: #dee2e6;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f8f9fa;
    }

    .table-hover tbody tr:hover {
        background-color: #e9ecef;
    }

    .nowrap {
        white-space: nowrap;
    }

    .projhdr h3 {
        color: #1a252f;
        font-weight: 700;
    }

    .projhdr h3 i {
        color: #0056b3;
        margin-right: 0.5rem;
    }

    /* TOC card styling */
    .sticky-top .card {
        border: 1px solid #dee2e6;
        background-color: #ffffff;
    }

    .sticky-top .card-body {
        padding: 1rem;
        color: #212529;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {

        .col-10,
        .col-2 {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .sticky-top {
            position: relative !important;
            top: auto !important;
        }

        #pagecontainer {
            padding: 0 15px;
        }
    }

    /* Smooth scrolling for anchor links */
    html {
        scroll-behavior: smooth;
    }

    /* Code highlighting */
    code {
        background-color: #e9ecef;
        color: #c7254e;
        padding: 0.2em 0.4em;
        border-radius: 3px;
        font-size: 0.875em;
        font-weight: 600;
    }

    pre code {
        background: none;
        color: #1f2937;
        padding: 0;
        font-weight: 500;
    }
</style>

<?php
$HtmlPage->PrintFooter();
