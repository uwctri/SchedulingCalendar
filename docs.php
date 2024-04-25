<?php
$HtmlPage = new HtmlPage();
$HtmlPage->addStylesheet("home.css", 'screen,print');
$HtmlPage->PrintHeader();
include APP_PATH_VIEWS . 'HomeTabs.php';

?>
<div class="projhdr"><i class="fas fa-calendar"></i> Scheduling & Availability Documentation </div>

<script>

</script>

<?php

$HtmlPage->PrintFooter();
?>