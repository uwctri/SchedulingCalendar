<?php
$HtmlPage = new HtmlPage();
$HtmlPage->addStylesheet("home.css", 'screen,print');
$HtmlPage->PrintHeader();
include APP_PATH_VIEWS . 'HomeTabs.php';

?>
<div class="projhdr"><i class="fas fa-calendar"></i> Scheduling & Availability Documentation </div>

General workflow
Every tab of the Calendar
Every setting in the config
Admin Tools
Location Settings Structure

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


DET Integration

<script>

</script>

<?php

$HtmlPage->PrintFooter();
?>