<?php
$module->initializeJavascriptModuleObject();
$module->tt_transferToJavascriptModuleObject();
$index = $module->getUrl('index.php');
?>
<script>
    const csrf = "<?= $module->getCSRFToken(); ?>"
    const router = "<?= $module->getUrl('router.php'); ?>"
    const project_code = "<?= $module->getProjectSetting('project-coode'); ?>"
</script>
<script src="<?= $module->getUrl('calendar.js'); ?>" defer></script>

<div class="projhdr">
    <i class="fas fa-calendar"></i> <?= $module->tt('module_name'); ?>
    <div id="pageMenu" class="btn-group float-right d-none">
        <a href="<?= "$index&type=edit" ?>" class="btn btn-sm btn-secondary type-edit">Edit Availability</a>
        <a href="<?= "$index&type=schedule" ?>" class="btn btn-sm btn-secondary type-schedule">Schedule</a>
        <a href="<?= "$index&type=my" ?>" class="btn btn-sm btn-secondary type-my">My Calendar</a>
    </div>
</div>
<div id="content" class="w-100 pr-4 d-none">
    <div id="calendarColumn">
        <div id="calendar"></div>
    </div>
</div>