<?php

namespace UWMadison\Scheduling;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;
use RestUtility;
use Project;

class Scheduling extends AbstractExternalModule
{
    /*
    Redcap Hook. Allow nav to the index page only if user rights are met
    */
    public function redcap_module_link_check_display($project_id, $link)
    {
        // TODO if SOT project then hide the project calendar
        // TODO if normal project hide the edit Availability/"My" calendar
        $link["url"] = $link["url"] . "&type=" . $link["type"];
        return $link;
    }

    public function loadSettings()
    {
        return [
            "csrf" => $this->getCSRFToken(),
            "router" => $this->getUrl('router.php')
        ];
    }

    /*
    Process a post request from API or router
    */
    public function process($tokenRequired)
    {
        global $Proj;

        $request = RestUtility::processRequest($tokenRequired);
        $params = $request->getRequestVars();
        $project_id = $params["projectid"] ?? $_GET["pid"];

        // API calls need to have a new project instance created
        if (!isset($Proj)) {
            $Proj = new Project($project_id);
        }

        // Only really needed for API, but just check for everyone
        if (!$this->isModuleEnabledForProject($project_id)) {
            RestUtility::sendResponse(400, "The requested module is currently disabled on this project.");
        }

        // Run core code
        // TODO
        return json_encode([
            [
                "title" => "test thing",
                "start" => date("Y-m-d") . "T11:00",
                "end" =>  date("Y-m-d") . "T13:00"
            ]
        ]);
    }

    /*
    Check if module is enabled on project
    */
    private function isModuleEnabledForProject($project_id)
    {
        return ExternalModules::getProjectSetting($this->PREFIX, $project_id, ExternalModules::KEY_ENABLED);
    }
}
