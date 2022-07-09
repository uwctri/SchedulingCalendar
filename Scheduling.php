<?php

namespace UWMadison\Scheduling;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;
use RestUtility;

class Scheduling extends AbstractExternalModule
{
    /*
    Redcap Hook. Allow nav to the index page only if user rights are met
    */
    public function redcap_module_link_check_display($project_id, $link)
    {
        return true;
    }

    public function loadSettings()
    {
        return json_encode([]);
    }

    /*
    Process a post request from API or router
    */
    public function process($tokenRequired)
    {
        global $Proj;

        $request = RestUtility::processRequest($tokenRequired);
        $params = $request->getRequestVars();
        $project_id = $params['projectid'];

        // API calls need to have a new project instance created
        if (!isset($Proj)) {
            $Proj = new Project($project_id);
        }

        // Only really needed for API, but just check for everyone
        if (!$this->isModuleEnabledForProject($project_id)) {
            self::errorResponse("The requested module is currently disabled on this project.");
        }

        // Run core code
        // TODO
        return json_encode([]);
    }

    /*
    Check if module is enabled on project
    */
    private function isModuleEnabledForProject($project_id)
    {
        return ExternalModules::getProjectSetting($this->PREFIX, $project_id, ExternalModules::KEY_ENABLED);
    }

    /*
    Send 400 error with message
    */
    private static function errorResponse($message)
    {
        self::sendResponse(400, $message);
    }

    /*
    Send rest response with status and message
    */
    private static function sendResponse($status = 200, $response = '')
    {
        RestUtility::sendResponse($status, $response);
    }
}
