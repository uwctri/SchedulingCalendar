<?php
$module->initializeJavascriptModuleObject();
$module->tt_transferToJavascriptModuleObject();
?>
<script>
    let glo = <?= json_encode($module->loadSettings()); ?>;
    glo.em = <?= $module->getJavascriptModuleObjectName(); ?>;
</script>

<div class="projhdr"><i class="fas fa-calendar"></i> <?= $module->tt('module_name'); ?></div>
<div class="w-100" style="min-width:1200px">


</div>
