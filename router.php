<?php

/** @var \UWMadison\Scheduling\LocalInstrumentLibrary $module */


try {
    $result = $module->process();
    RestUtility::sendResponse(200, $result, 'json');
} catch (Exception $ex) {
    RestUtility::sendResponse(400, $ex->getMessage());
}
