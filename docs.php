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
                    This external module adds comprehensive scheduling functionality to the REDCap platform.
                    It allows users (also called providers below) to set their availability and then allows other users
                    to schedule appointments with subjects against that availability. The goal is to eliminate the need to use software
                    outside of REDCap. While there does exist a native Calendar module in REDCap that is useful for smaller teams or projects, some may
                    not find it suitable when dealing with larger teams that work across multiple projects, at different times, or in different locations.
                    <br><br>
                    Please understand that this module may be difficult to initially set up and use. It is recommended that you read through the documentation
                    and reach out to the developers (via a <a href="https://github.com/uwctri/SchedulingCalendar/issues/new">GitHub issue</a> or <a href="mailto:adam.nunez@ctri.wisc.edu">email</a>) for help if you are having trouble.
                </div>
            </div>
            <div id="workflow" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Workflow</div>
                <div class="card-body">
                    Workflow can be broken down into two main parts, scheduling availability and scheduling appointments.
                    <br><br>
                    When scheduling availability we expect a provider to use the calendar to enter when they plan to be available to see subjects for the study.
                    This could be 9-5 Monday through Friday, some other set schedule, or manually entered specific times on days. Options exist to bulk set a
                    schedule for the provider and then make modifications for any exceptions that exist. A "Calendar Admin" can also edit availability on behalf
                    of a provider.
                    <br><br>
                    When scheduling appointments we expect a provider to use the calendar to find a time that a provider is available and then schedule a subject
                    for that time. Any provider can schedule a subject for any other provider. The provider can also edit the appointment after it is scheduled,
                    with some limitations.
                </div>
            </div>
            <div id="config" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Project Configuration</div>
                <div class="card-body">
                    It is advised that you require module-specific user privileges to access the configuration settings due to the complexity of settings in the module.
                    <br><br>
                    <h5 class="text-decoration-underline">General Configuration</h5>
                    <p><b>Calendar Admin</b><br>
                        Admins may edit any user's calendar and have access to two additional tools:<br>
                        1. Data cleanup - remove older availability and appointments for withdrawn subjects<br>
                        2. ICS Exports - export a calendar file for viewing in an outside application. This file will contain PHI.<br>
                        See below for additional information on both tools.</p>
                    <p><b>Unschedulable</b><br>
                        This is a list of users that have access to the project, availability on the calendar, but shouldn't be schedulable.
                        This is useful if a team member was originally a provider and now has a purely administrative role.</p>
                    <p><b>Trigger DET</b><br>
                        This is a technical feature and requires a developer to implement.
                        Sends a POST to the Data Entry Trigger on calendar save. See below for details on the structure of the POST request.</p>
                    <p><b>Name Field</b><br>
                        Variable to index, search, and display as the subject's name in various places on the calendar.
                        This should be a full name, i.e. a concatenation of the first and last name.</p>
                    <p><b>Withdraw Flag</b><br>
                        This field should be set to indicate that a subject has been withdrawn from the study and is no longer schedulable.
                        This flag field should be set to any non-blank, non-zero value. Future appointments are not removed from the calendar.
                        You can either manually remove them or clean them up using the admin tools.</p>
                    <p><b>Default Location</b><br>
                        Typically the location for an appointment is left blank and is selected by the provider when scheduling. You can, however,
                        choose to default the location to either a field's value or a static value. Both options below use the coded location value from the location JSON.<br>
                        <strong>Location Field</strong> - Useful if subjects have preferred clinics that are imported or selected early in the study.<br>
                        <strong>Location Value</strong> - Useful if the vast majority of appointments (or all of them) will occur at one location.
                    </p>
                    <p><b>Location Source</b><br>
                        Locations in the module are defined via a JSON object, an easily-readable format that lists all locations, display names, coded values, and metadata.
                        The structure of this data is detailed below. The data can either be stored on the current project or pulled from another project.
                        This is useful if two or more studies share a location structure.<br>
                        <strong>Location JSON</strong> - If the location JSON is local, it should be minified and saved here. See below for format.<br>
                        <strong>Location Project</strong> - Select another project to copy the location JSON from.
                        This is not a one-time copy; the location structure will always be looked up from this other project.
                        This makes it easy to maintain a single source of truth for the location structure.
                    </p>
                    <h5 class="text-decoration-underline">Schedulable Visit Configuration</h5>
                    <p>All settings in this section are repeated for every schedulable event on the calendar.</p>
                    <p><b>Display Name</b><br>
                        The name of the event to be displayed on the calendar, in dropdowns etc.</p>
                    <p><b>Internal Coded Value</b><br>
                        Short unique code used on the backend for scheduling.</p>
                    <p><b>Linked Event</b><br>
                        A REDCap event to associate this schedulable visit to. Data is pulled from and written to this event for the Shared Schedulable Visit Config below.</p>
                    <p><b>Notes</b><br>
                        Any notes to be displayed on the subject summary for this event. Useful for displaying visit instructions or other information.</p>
                    <p><b>Branching Logic</b><br>
                        REDCap branching logic to determine when this visit type should be available for scheduling. Leave blank to always show this visit type.</p>
                    <p><b>Duration</b><br>
                        Duration of the event in minutes. This is used to calculate the end time of the event when scheduling.</p>
                    <p><b>Allow Additional Time</b><br>
                        Allow the provider to add additional time to the event when scheduling. This is useful if the provider needs to add time for a specific subject. When enabled, the duration above becomes the minimum visit duration.</p>
                    <p><b>Allow Any Location</b><br>
                        Allow the provider to schedule this event at any location, regardless of the location listed on the availability. This is useful for calls or other virtual visits.</p>
                    <h5 class="text-decoration-underline">Shared Schedulable Visit Config</h5>
                    <p><b>Date/Time Writeback</b><br>
                        When a subject is scheduled for an event, the date (and/or time) of the event will be written back to the linked event in this field based on the validation enabled on the field. All date, time, and datetime formats are supported. This is useful for tracking when a subject was scheduled for a visit, displaying the information in forms, etc.</p>
                    <p><b>Provider Writeback</b><br>
                        When a subject is scheduled for an event, the provider that the event is scheduled with will be written back to the linked event in this field. This is useful for tracking who has visits, displaying the information in forms, etc.</p>
                    <p><b>Visit Range Start/End</b><br>
                        If your visit has a valid schedulable range calculated or manually entered somewhere in your project, you can enter the field names here. This is useful for preventing scheduling outside of the valid range.</p>
                    <h5 class="text-decoration-underline">Subject Summary Configuration</h5>
                    <p><b>Additional Field</b><br>
                        Add extra fields to the right-side subject summary. Useful for displaying email, phone, or other demographic information.</p>
                    <h5 class="text-decoration-underline">ICS Export Config</h5>
                    <p><b>Additional Field</b><br>
                        Add extra fields to the description of the event in the calendar's ICS exports.</p>
                </div>
            </div>
            <div id="sys" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">System Configuration</div>
                <div class="card-body">
                    These settings can only be set by a REDCap administrator in the Control Center.
                    It is not necessary to update these settings for every new project, but projects that share availability will need to be configured here.
                    <p><b>Availability Groups</b><br>
                        When adding availability to the calendar providers associate it with a particular group.
                        This group can be used by one project only, or shared with other projects. It is common to have projects that use only
                        the "local" availability group, i.e. the group associated with that project only, however if a team of providers is
                        working on multiple studies at once they may wish to enter availability into a specific group and then share that group
                        across projects.<br>
                        Name - Display name for the availability group.<br>
                        Code - Coded value for the group.</p>
                    <p><b>Project Availability Group</b><br>
                        To associate the created availability groups (above) with any number of projects, we update this list.<br>
                        <strong>Project</strong> - The existing project using the module<br>
                        <strong>Code</strong> - Coded value of the availability group. If listing multiple, then enter a comma-delimited list.
                    </p>
                    <p><b>Allow Global Group</b><br>
                        The "Global Group" is an availability group that is shared across all projects. If your REDCap instance has a small
                        number of regular users, you may wish to enable this so all, or most, availability can exist under one group.</p>
                    <p><b>Prevent Local Group</b><br>
                        By default, all projects start with a "local" availability group that only the one project can access. You can turn that
                        feature off here if you want to ensure that custom groups are always used.</p>
                </div>
            </div>
            <div id="admin" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Admin Tools</div>
                <div class="card-body">
                    Admin tools are available to users who are listed as "Calendar Admin" in the general settings.
                    A handful of tools are available to these users to help manage the calendar, all located in the bottom left corner of the calendar.
                    <p><b>Clean Up</b><br>
                        Using the clean up tool will remove all availability in the past and/or appointments for withdrawn subjects.
                        This is useful to keep the calendar clean, easy to use, and can (very slightly) improve performance.</p>
                    <p><b>ICS Export/Link</b><br>
                        Download a copy of the calendar as an ICS file that can be imported into Outlook, Google Calendar, or any other
                        service. This calendar will contain PHI. A link can also be generated to live-stream the calendar to another application.</p>
                    <p><b>Color Configuration</b><br>
                        By default, users are given random colors for their availability and appointments. In the color configuration, the admin
                        can assign permanent colors for each user.</p>
                </div>
            </div>
            <div id="locs" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Location Settings Structure</div>
                <div class="card-body">
                    Locations are structured as a JSON object that is easily readable and can be stored in a REDCap project or pulled from another project.
                    Currently, the location structure is only used for construction of dropdowns and display names. In the future, the structure will allow
                    for location matching and matching to sub-locations. Two examples of the structure are below, both are very simple. Currently there is no need to build out sub-locations.
                    <div class="container">
                        <div class="row">
                            <div class="col-6">
                                <pre>
                                <code>
                                    {
                                        "WFH": {
                                            "name": "Work From Home",
                                            "active": true,
                                            "in_person": false
                                        },
                                        "CTRIMAD": {
                                            "name": "Madison Office",
                                            "active": true,
                                            "sub": {
                                                "CMADE": {
                                                    "name": "Madison East",
                                                    "active": true
                                                },
                                                "CMADW": {
                                                    "name": "Madison West",
                                                    "active": true
                                                }
                                            }
                                        },
                                        "CTRIMKE": {
                                            "name": "Milwaukee Office",
                                            "active": true
                                        }
                                    }
                                </code>
                                </pre>
                                <p>Simple example of a location build out with multiple on-site locations and one "calls only" location.</p>
                            </div>
                            <div class="col-6">
                                <pre>
                                <code>
                                    {
                                        "call": {
                                            "name": "Call",
                                            "active": true,
                                            "in_person": false
                                        },
                                        "site": {
                                            "name": "In Office",
                                            "active": true,
                                            "in_person": true
                                        }
                                    }
                                </code>
                                </pre>
                                <p>If you have no need for locations you can list only one or two, one for calls and one for in-person visits.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="det" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">DET Integration</div>
                <div class="card-body">
                    This is a technical feature and requires a developer to implement.
                    <br><br>
                    If this feature is enabled in the configuration, the module will send a message to the DET endpoint,
                    if one is set for the project, when anything (appointment or availability) is added, removed, or updated on the calendar.
                    The message sent will be a POST request with a JSON body formatted as below. The endpoint should respond with a 200 status
                    code immediately upon receipt of the message and carry out any additional processing asynchronously.
                    <br><br>
                    <strong>Note:</strong> The internal ID is a unique identifier for the appointment or availability. It is not the same as the record_id or any other identifier in REDCap.
                    Currently it is only useful if you are able to directly query the database for more information. This limits the usefulness of the delete and update messages.
                    In the future, all information on the deleted or updated appointment or availability will be sent in the message.
                    <pre>
                        <code>
                            {
                                redcap_url: Root URL of the REDCap installation,
                                project_url: URL to the associated project ending in "/index.php?pid=[project-id]",
                                project_id: The project id of the current project,
                                username: The username of the current user,
                                resource: Enum describing the resource that was impacted (Availability, Appointment),
                                crud: Enum describing the operation that occurred (create, update, delete. Read is never sent),
                                msg: String message describing the operation that occurred,
                                ___
                                // Only sent when resource is Appointment and crud is create
                                start: Start datetime in ISO format,
                                end: End datetime in ISO format,
                                providers: Array of size one with provider username,
                                locations: Array of size one with location name,
                                subjects: Array of size one with subject record_id,
                                visits: Array of size one with visit code,
                                notes: Any notes that were added,
                                ___
                                // Only sent when resource is Availability and crud is create
                                start: Start datetime in ISO format,
                                end: End datetime in ISO format,
                                providers: Array of size one with provider username,
                                locations: Array of size one with location name,
                                group: Availability code (also called group code),
                                ___
                                // Only sent when crud is update
                                id: Internal id of the appointment or availability,
                                providers: Array of size one with the new provider username (or previous if not changed),
                                locations: Array of size one with the new location name (or previous if not changed),
                                ___
                                // Only sent when crud is delete
                                id: Internal id of the appointment or availability (now deleted),
                                data: Full data structure of the appointment or availability that was deleted
                            }
                        </code>
                    </pre>
                    If you decide to implement this feature and would like to use the internal ID to query the database for more information, see below for an example of how to do so.
                    Normally DETs can be hosted on any server, but this DET would need to be hosted on the same server as the REDCap installation so the SQL database can be directly queried using the unique ID.
                    <pre>
                        <code>
                            define("NOAUTH", true);
                            require_once  "../redcap_connect.php";
                            $sql = "SELECT * FROM em_scheduling_calendar WHERE id = ?";
                            $result = db_query($sql, $_POST["id"]);
                            $data = db_fetch_assoc($result)
                            // Do something with the data:
                            // project_id, visit, availability_code, user, 
                            // record, location, time_start, time_end, notes
                        </code>
                    </pre>
                </div>
            </div>
            <div id="query" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">URL Query Parameters</div>
                <div class="card-body">
                    When building a form in REDCap you might want to add a button to send the user to the calendar, probably to schedule an appointment as part of the workflow.
                    When linking to the calendar from an instrument, or any external source, you can append a few extra query parameters to configure things.

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
                                <td>Preselect a subject. Useful if you are adding a link in a form to instruct the user to schedule the current subject.</td>
                            </tr>
                            <tr>
                                <td><b>date</b></td>
                                <td>Start date in Y-M-D format. By default the calendar shows the current week. Useful if the scheduled appointment should be some number of days out.</td>
                            </tr>
                            <tr>
                                <td><b>refer</b></td>
                                <td>Set to either "true" or an encoded URL. When set, a "Return to workflow" button is shown in the bottom right corner that will send the user back to the referring page or to the encoded URL.</td>
                            </tr>
                        </tbody>
                    </table>

                    Custom buttons are usually added into descriptive fields and, as a result, normal REDCap field piping and smart variables can be used to build the URL.
                    <br><br>
                    A typical link might look like this:
                    <pre>
                        <code>
                            [redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]
                        </code>
                    </pre>

                    A URL using both date and record might look like the below. Notice we have piped the date value from a REDCap field:
                    <pre>
                        <code>
                            [redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]&record=[record-name]&date=[some_date_field]
                        </code>
                    </pre>

                    A URL using refer can use either "true" or an encoded URL. To do the latter requires some JavaScript to encode the URL. Consider using the Shazam EM for this:
                    <pre>
                        <code>
                            // Send the user to google after they are done with the calendar
                            let refer = encodeURIComponent("https://www.google.com")
                            // Set this URL on a button or link. redcap_version is a global variable set by REDCap. 
                            let url = `${redcap_version}/ExternalModules/?prefix=scheduling_calendar&page=index&pid=${pid}&refer=${refer}`
                            url = location.href.split(redcap_version)[0] + url
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
