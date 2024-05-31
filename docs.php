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
            <div class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Calendar Basics</div>
                <div class="card-body">
                    Go over every tab, scheduling, availability, my. And how to use.
                </div>
            </div>
            <div class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Configuration</div>
                <div class="card-body">
                    Go over all configuration options
                </div>
            </div>
            <div class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Admin Tools</div>
                <div class="card-body">
                    Go over every tab, scheduling, availability, my. And how to use.
                </div>
            </div>
            <div class="card my-4 card-primary">
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
            <div class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">DET Integration</div>
                <div class="card-body">
                    Go over every tab, scheduling, availability, my. And how to use.
                </div>
            </div>
            <div class="card my-4 card-primary">
                <div class="card-header text-white fw-bold bg-primary bg-gradient">Query Parameters</div>
                <div class="card-body">
                    When linking to the calendar from an an instrument or an external source, you can use a few query parameters to configure the calendar.

                    <table class="table my-3">
                        <tr>
                            <td><b>record</b> or <b>id</b></td>
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

                    A typical link might look like this:
                    <pre>
                        <code>
                            [redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]
                        </code>
                    </pre>

                    A URL using both date and record might look like the below.
                    <pre>
                        <code>
                            [redcap-version-url]ExternalModules/?prefix=scheduling_calendar&page=index&pid=[project-id]&record=[record-name]&date=[some_date_field]
                        </code>
                    </pre>

                    A URL using refer can use either "true" or an encoded URL. To do the latter requires some javascript to encode the URL. Consider using the Shazam EM for this.
                    <pre>
                        <code>
                            let refer = encodeURIComponent(location.href)
                            // Set this URL on a button or link. redcap_version is a global variable set by REDCap. 
                            let url = location.href.split(redcap_version)[0] + `${redcap_version}/ExternalModules/?prefix=scheduling_calendar&page=index&pid=${pid}&refer=${refer}`
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
        let t = $(el).text().split('\n').filter((line) => line.trim().length > 0)
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
        font-size: 1.35em;
    }

    .card-body {
        font-size: 1.2em;
    }

    pre {
        margin-top: 1em;
        margin-top: 1em;
    }
</style>