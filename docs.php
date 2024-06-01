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
                    It allows users, refered to providers below, to set their availability and then for other users
                    to schedule appointments with subjects against that availability. The goal is to eliminate the need for use of software
                    outside of REDCap. While their does exist a native Calendar module in REDCap that is useful for smaller teams or projects, some may
                    not find it suitable when dealing with larger teams that work across multiple projects, at different times, or in different locations.
                    <br><br>
                    Please understand that this module may be difficult to initaly setup and use. It is recommended that you read through the documentation
                    and reach out to the developers (via a Github issue or email) for help if you are having trouble.
                </div>
            </div>
            <div id="workflow" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">General Workflow</div>
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
            <div id="basics" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Calendar Basics</div>
                <div class="card-body">
                    Go over every tab, scheduling, availability, my. And how to use.
                </div>
            </div>
            <div id="config" class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Configuration</div>
                <div class="card-body">
                    Go over all configuration options
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
                    If this feature is enabled in the configuration, the module will send a message to the DET endpoint,
                    if one is set for the project, when anything (appointment or availability) is added, removed, or updated on the calendar.
                    The message sent will be a POST request with a JSON body formatted as below. The endpoint should respond with a 200 status
                    code message immediately upon receipt of the message and carry out any additional processing asynchronously.
                    <br><br>
                    Note: The internal id is a unique identifier for the appointment or availability. It is not the same as the record_id or any other identifer in REDCap.
                    Currently it is only useful if you are able to directly query the database for more information. This limits the usefulness of the delete and update messages.
                    <pre>
                        <code>
                            {
                                redcap_url: Root URL of the REDCap installation,
                                project_url: URL to the assoicated projecting ending in "/index.php?pid=[project-id]",
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

<?php

$HtmlPage->PrintFooter();
?>
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