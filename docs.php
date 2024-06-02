<?php
$HtmlPage = new HtmlPage();
$HtmlPage->addStylesheet("home.css", 'screen,print');
$HtmlPage->PrintHeader();
include APP_PATH_VIEWS . 'HomeTabs.php';
?>
<div class="projhdr">
    <h3><i class="fas fa-calendar"></i> Scheduling & Availability Documentation</h3>
</div>

<div class="bg-white container m-0 p-0">
    <div class="row">
        <div class="col-10">
            <div id="purpose" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Purpose</div>
                <div class="card-body">
                    This external module adds afirmative scheduling to the REDCap platform.
                    It allows users, also called providers below, to set their availability and then for other users
                    to schedule appointments with subjects against that availability. The goal is to eliminate the need for use of software
                    outside of REDCap. While their does exist a native Calendar module in REDCap that is useful for smaller teams or projects, some may
                    not find it suitable when dealing with larger teams that work across multiple projects, at different times, or in different locations.
                    <br><br>
                    Please understand that this module may be difficult to initaly setup and use. It is recommended that you read through the documentation
                    and reach out to the developers (via a <a>Github issue</a> or email) for help if you are having trouble.
                </div>
            </div>
            <div id="workflow" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Workflow</div>
                <div class="card-body">
                    GO MORE IN DEPTH. TALK ABOUT ALL THE TABS AND WHAT INFO IS USED WHERE.<br>
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
                    It is advisable require module-specific user privileges to access the configuration settings due to the complexity of settings in the module. The project settings are broken down into three categories: General, System Configuration, and Admin Tools.

                    <p><b>Calendar Admin</b><br>
                        Admins may edit any user's calendar and have access to two additional tools:<br>
                        1. Data cleanup - remove older availability and appointments for withdrawn subjects<br>
                        2. ICS Exports - export a calendar file for viewing in an outside application. This file will contain PHI.<br>
                        See below for additional information on both tools.</p>
                    <p><b>Unschedulable</b><br>
                        This is a list of users that have access to the project, availability on the calendar, but shouldn't be schedulable.
                        This is useful if a team member was orignally a provider and now has a purley administrative role.</p>
                    <p><b>Trigger DET</b><br>
                        This is a highly technical feature and requires a developer to implement.
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
                        Location Field - Useful if subject's have prefered clinics that are imported or selected early in the study. <br>
                        Location Value - Useful if the vast majority of appointments (or all of them) will occur at one location.</p>
                    <p><b>Location Source</b><br>
                        Locations in the module are defined via a JSON object, an easily-to-read format that lists all locations, display names, coded values, and metadata.
                        The structure of this data is detailed below. The data can either be stored on the current project or pulled from another project.
                        This is useful if two or more studies share a location structure<br>
                        Location JSON - If the location JSON is local is should be minified and saved here. See below for format<br>
                        Location Project - Select another project to copy the location JSON from.
                        This is not a one time copy, the location structure will always be looked up from this other project.
                        This makes it easy to maintain a single source-of-truth for the location structure</p>
                    <br>
                    MISSING SETTINGS HERE
                </div>
            </div>
            <div id="sys" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">System Configuration</div>
                <div class="card-body">
                    These settings can only be set by a REDCap administrator in the Control Center.
                    It is necissary to update some of these settings any time a new project is created that will use the module.
                    <p><b>Availability Groups</b><br>
                        When adding availability to the calendar providers associate it with a particular group.
                        This group can be used by one project only, or shared with other projects. It is common to have projects that use only
                        the "local" availability group, i.e. the group associated with that project only, however if a team of providers is
                        working on multiple studies at once they may wish to enter availability into a specific group and then share that group
                        across projects.<br>
                        Name - Display name for the availability group.<br>
                        Code - Coded value for the group.</p>
                    <p><b>Project Availability Group</b><br>
                        To associate the created availability groups (above) with any number of projects we update this list.<br>
                        Project - The existing project using the module<br>
                        Code - Coded value of the availability group. If listing multiple then enter a comma deliminted list. </p>
                    <p><b>Allow Global Group</b><br>
                        The "Global Group" is an availability group that is shared across all projects. If your REDCap instance has a small
                        number of regular users you may wish to enable this so all, or most, availability can exist under one group.</p>
                    <p><b>Prevent Local Group</b><br>
                        By default all projects start with a "local" availability group that on the one project can access. You can turn that
                        feature off here if you want to insure that custom groups are always used.</p>
                </div>
            </div>
            <div id="admin" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Admin Tools</div>
                <div class="card-body">
                    Go over every tab, scheduling, availability, my. And how to use.
                </div>
            </div>
            <div id="locs" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Location Settings Structure</div>
                <div class="card-body">
                    Go over every tab, scheduling, availability, my. And how to use.
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
                </div>
            </div>
            <div id="det" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">DET Integration</div>
                <div class="card-body">
                    This is a highly technical feature and requires a developer to implement.
                    <br><br>
                    If this feature is enabled in the configuration, the module will send a message to the DET endpoint,
                    if one is set for the project, when anything (appointment or availability) is added, removed, or updated on the calendar.
                    The message sent will be a POST request with a JSON body formatted as below. The endpoint should respond with a 200 status
                    code message immediately upon receipt of the message and carry out any additional processing asynchronously.
                    <br><br>
                    Note: The internal id is a unique identifier for the appointment or availability. It is not the same as the record_id or any other identifer in REDCap.
                    Currently it is only useful if you are able to directly query the database for more information. This limits the usefulness of the delete and update messages.
                    In the future all information on the deleted or updated appointment or availability will be sent in the message.
                    <pre>
                        <code>
                            {
                                redcap_url: Root URL of the REDCap installation,
                                project_url: URL to the associated project ending in "/index.php?pid=[project-id]",
                                project_id: The project id of the current project,
                                username: The username of the current user,
                                resource: Enum describing the resource that was impacted (Availability, Appointment),
                                crud: Enum describing the operation that occured (create, update, delete. Read is never sent),
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
                                id: Internal id of the appointment or availability
                            }
                        </code>
                    </pre>
                    If you decide to implement this feature and would like to use the internal id to query the database for more information, see below for an example of how to do so.
                    This DET would need to be hosted on the same server as the REDCap installation so we can use the "redcap_connect" script and query the database.
                    <pre>
                        <code>
                            define("NOAUTH", true);
                            require_once  "../redcap_connect.php";
                            $sql = "SELECT * FROM em_scheduling_calendar WHERE id = ?";
                            $result = db_query($sql, $_POST["id"]);
                            while ($row = db_fetch_assoc($result)) {
                                // Do something with the data:
                                // project_id
                                // visit
                                // availability_code
                                // user
                                // record
                                // location
                                // time_start
                                // time_end
                                // notes
                            }
                        </code>
                    </pre>


                </div>
            </div>
            <div id="query" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Query Parameters</div>
                <div class="card-body">
                    When building a form in REDCap you might want to add a button to send the user to the calendar; probably to schedule an appointment as a part of the workflow.
                    When linking to the calendar from an an instrument, or any external source, you can appened a few extra query parameters to configure things.

                    <table class="table my-3">
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
                            <td>Set to either "true" or an encoded URL. When set a "Return to workflow" button is shown in the bottom right corner that will send the user back to the refering page or to the encoded URL. </td>
                        </tr>
                    </table>

                    Custom buttons are usually added into descriptive fields and, as a result, normal REDCap field piping and smart variables can be used to build the URL.
                    <br>
                    A typical link might look like this:
                    <pre>
                        <code>
                            [redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]
                        </code>
                    </pre>

                    A URL using both date and record might look like the below. Notice we have piped the date value from a REDCap field.
                    <pre>
                        <code>
                            [redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]&record=[record-name]&date=[some_date_field]
                        </code>
                    </pre>

                    A URL using refer can use either "true" or an encoded URL. To do the latter requires some javascript to encode the URL. Consider using the Shazam EM for this.
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
        <div class="sticky-top top-4 col-2 h-100 pt-4" style="top:4em">
            <strong class="d-block h5 my-2 pb-2 border-bottom">Table of Contents</strong>
            <nav id="toc"> </nav>
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

    .card-header {
        font-size: 1.25em;
    }

    .card {
        scroll-margin-top: 4em;
    }

    .card-body,
    #toc a {
        font-size: 1.1em;
        color: black;
    }

    pre {
        margin-top: 1em;
        margin-top: 1em;
    }
</style>

<?php
$HtmlPage->PrintFooter();
