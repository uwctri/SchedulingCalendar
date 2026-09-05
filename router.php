<?php

/** @var \UWMadison\Scheduling\LocalInstrumentLibrary $module */


try {
    $result = $module->process();
    $status = http_response_code();
    if (!$status || $status === 200)
        $status = 200;
    RestUtility::sendResponse($status, $result, 'json');
} catch (Exception $ex) {
    RestUtility::sendResponse(400, json_encode([
        "success" => false,
        "msg" => $ex->getMessage()
    ]), 'json');
}
