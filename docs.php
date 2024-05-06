<?php
$HtmlPage = new HtmlPage();
$HtmlPage->addStylesheet("home.css", 'screen,print');
$HtmlPage->PrintHeader();
include APP_PATH_VIEWS . 'HomeTabs.php';
// TODO finish these docs

?>
<div class="projhdr"><i class="fas fa-calendar"></i> Scheduling & Availability Documentation </div>

<body class="bg-light">
    <div class="container">
        <div class="card my-4 card-primary">
            <div class="card-header text-white fw-bold bg-primary bg-gradient">Purpose</div>
            <div class="card-body">
                The Report Tweaks external module allows end-users to apply a variety of tweaks to individual reports
                from the "Edit Report" page. These tweaks currently include:
                <ul>
                    <li>Include/Exclude the <code>redcap_event_name</code> from the report. Useful for styling the
                        report for smaller screens if the column isn't needed. </li>
                    <li>Combine (merge) rows representing the same record on the report. </li>
                    <li>Remove rows with no data. Useful if your report filtering occasionally returns blank rows.</li>
                    <li>Show the reports filter logic. Useful if advanced users want to see the report's logic, but shouldn't be allowed to edit the report.</li>
                    <li>Add a date-range live filter to filter rows down to those with a date in a common time range</li>
                    <li>Collapse longer report descriptions</li>
                    <li>Add one or multiple "writeback" buttons to the report to update values in the database for all rows in a report. </li>
                </ul>
                Regardless of configuration the following additions are made to all reports:
                <ul>
                    <li>Minimum and Maximum search boxes are added to find arbitrary ranges on a selected column</li>
                    <li>A copy button is added that copies all visible data in the report</li>
                    <li>Checkboxes to toggle showing REDCap generated columns on the report</li>
                </ul>
            </div>
        </div>
        <div class="card my-4 card-primary">
            <div class="card-header text-white fw-bold bg-primary bg-gradient">General Workflow</div>
            <div class="card-body">
                The Report Tweaks external module allows end-users to apply a variety of tweaks to individual reports
                from the "Edit Report" page. These tweaks currently include:
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
                Go over every tab, scheduling, availability, my. And how to use.
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
                <pre><code>{
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
}</code></pre>
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
                date, id or record, refer
            </div>
        </div>
    </div>
</body>

<?php

$HtmlPage->PrintFooter();
?>