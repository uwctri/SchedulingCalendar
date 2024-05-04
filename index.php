<?php
$module->initializeJavascriptModuleObject();
$module->tt_transferToJavascriptModuleObject();
$jsObj = $module->getJavascriptModuleObjectName();
$index = $module->getUrl('index.php');
?>
<script>
    <?= $jsObj; ?>.project_name = "<?= $module->getProjectName(); ?>"
    <?= $jsObj; ?>.router = "<?= $module->getUrl('router.php'); ?>"
    <?= $jsObj; ?>.email = "<?= $module->getContactEmail(); ?>"
    <?= $jsObj; ?>.user = <?= json_encode($module->currentUser()); ?>
</script>
<link rel="stylesheet" href="<?= $module->getUrl('style.css'); ?>">
<script src="<?= $module->getUrl('index.js'); ?>" defer></script>

<div class="projhdr">
    <i class="fas fa-calendar"></i> <?= $module->tt('module_name'); ?>
    <div id="pageMenu" class="btn-group float-right d-none">
        <a href="<?= "$index&type=edit" ?>" class="btn btn-sm btn-secondary type-edit"><?= $module->tt('btn_edit'); ?></a>
        <a href="<?= "$index&type=schedule" ?>" class="btn btn-sm btn-secondary type-schedule"><?= $module->tt('btn_schedule'); ?></a>
        <a href="<?= "$index&type=my" ?>" class="btn btn-sm btn-secondary type-my"><?= $module->tt('btn_mycal'); ?></a>
    </div>
</div>
<div id="content" class="d-none">
    <div id="calendarColumn">
        <div id="calendar"></div>
        <div id="loader"></div>
    </div>
    <div class="col d-none" id="subjectSummary">
        <div class="card">
            <div class="card-header">
                <div class="subjectName"></div>
                <!-- TODO show some configurable summary of info here -->
            </div>
            <div class="card-body">
                <div class="row no-gutters">
                    <div class="col card-title eventName"></div>
                    <div class="col no-gutters">
                        <div class="row no-gutters">
                            <div class="text-right"><span class="dot">●</span></div>
                        </div>
                    </div>
                </div>
                <p class="card-text eventNotes d-none"></p>
            </div>
        </div>
    </div>
</div>