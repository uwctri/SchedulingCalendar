<?php
$module->initializeJavascriptModuleObject();
$module->tt_transferToJavascriptModuleObject();
$module->initCalendar();
?>
<script>
    let php = <?= json_encode($module->loadSettings()); ?>;
    php.em = <?= $module->getJavascriptModuleObjectName(); ?>;
</script>
<script src="<?= $module->getUrl('calendar.js'); ?>" defer></script>

<div class="projhdr"><i class="fas fa-calendar"></i> <?= $module->tt('module_name'); ?></div>
<div id="content" class="w-100 pr-4 d-none">
    <div id="calendarColumn">
        <div id="calendar"></div>
        <div id="footerbar">
            <select id="calendar-filter" multiple></select>
        </div>
    </div>
</div>