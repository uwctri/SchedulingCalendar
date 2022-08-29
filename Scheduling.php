<?php

namespace UWMadison\Scheduling;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;
use RestUtility;
use Project;
use Design;
use MetaData;

class Scheduling extends AbstractExternalModule
{
    /*
    Redcap Hook
    */
    public function redcap_module_link_check_display($project_id, $link)
    {
        if ($this->getProjectSetting('is-sot')) {
            return null;
        }
        return $link["url"] . "&type=" . $link["type"];
    }

    public function redcap_every_page_top()
    {
        if ($this->isPage("ExternalModules/manager/project.php")) {
            if ($this->getProjectSetting('is-sot')) {
                $this->initSotProject();
            }
            echo "<script src='{$this->getUrl('config.js')}'> </script>";
        }
    }

    public function loadSettings()
    {
        return [
            "csrf" => $this->getCSRFToken(),
            "router" => $this->getUrl('router.php')
        ];
    }

    public function initSotProject()
    {
        if (!$this->getProjectSetting("is-sot")) {
            return;
        }

        // Validate the current data dictionary
        $csv_file = $this->getUrl("sot.csv");
        $csv = file_get_contents($csv_file);
        $current_dd = str_replace(["\"", "\r"], "", REDCap::getDataDictionary('csv'));
        if ($current_dd == str_replace(["\"", "\r"], "", $csv)) {
            return;
        }

        // Prep to correct the dd
        $dd_array = Design::excel_to_array($csv_file, ",");
        db_query("SET AUTOCOMMIT=0");
        db_query("BEGIN");

        //Create a data dictionary snapshot of the *current* metadata and store the file in the edocs table
        MetaData::createDataDictionarySnapshot();

        $sql_errors = MetaData::save_metadata($dd_array);
        if (count($sql_errors) > 0) {
            // ERRORS OCCURRED, so undo any changes made
            db_query("ROLLBACK");
            // Set back to previous value
            db_query("SET AUTOCOMMIT=1");
        } else {
            // COMMIT CHANGES
            db_query("COMMIT");
            // Set back to previous value
            db_query("SET AUTOCOMMIT=1");
        }
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

        // Check method and page type to take action
        if ($params["action"] == "fetch") {
            // TODO
            return json_encode([
                [
                    "title" => "test thing",
                    "start" => date("Y-m-d") . "T11:00",
                    "end" =>  date("Y-m-d") . "T13:00"
                ]
            ]);
        }

        if ($params["action"] == "save") {
            // TODO perform the save
            if ($this->getProjectSetting('fire-det')) {
                $this->fireDataEntryTrigger($params);
            }
        }
    }

    private function fireDataEntryTrigger($saveParams)
    {
        // Chunks of this function are lifted from the DataEntry class
        global $data_entry_trigger_url, $data_entry_trigger_enabled;
        $redcap_version = REDCAP_VERSION;
        $longitudinal = REDCap::isLongitudinal();

        // Check if enabled
        if (!$data_entry_trigger_enabled || $data_entry_trigger_url == '') {
            return false;
        }

        // Build HTTP Post request parameters to send
        $params = array(
            'redcap_url' => APP_PATH_WEBROOT_FULL,
            'project_url' => APP_PATH_WEBROOT_FULL . "redcap_v{$redcap_version}/index.php?pid=" . PROJECT_ID,
            'project_id' => PROJECT_ID, 'username' => USERID
        );

        // Add in stuff from save
        $params = array_merge($params, $saveParams);

        if ($longitudinal) {
            $params['redcap_event_name'] = REDCap::getEventNames(True, True, $params["event_id"]);
        }

        // Set timeout value for http request
        $timeout = 10; // seconds
        // If $data_entry_trigger_url is a relative URL, then prepend with server domain
        $pre_url = "";
        if (substr($data_entry_trigger_url, 0, 1) == "/") {
            $pre_url = (SSL ? "https://" : "http://") . SERVER_NAME;
        }
        // Send Post request
        $response = http_post($pre_url . $data_entry_trigger_url, $params, $timeout);
        // Return boolean for success
        return !!$response;
    }

    /*
    Check if module is enabled on project
    */
    private function isModuleEnabledForProject($project_id)
    {
        return ExternalModules::getProjectSetting($this->PREFIX, $project_id, ExternalModules::KEY_ENABLED);
    }
}
