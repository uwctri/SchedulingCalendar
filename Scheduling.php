<?php

namespace UWMadison\Scheduling;

use ExternalModules\AbstractExternalModule;
use REDCap;
use RestUtility;
use Project;
use Design;
use MetaData;

class Scheduling extends AbstractExternalModule
{
    public function redcap_module_link_check_display($project_id, $link)
    {
        return $this->getProjectSetting('is-sot') ? null : $link;
    }

    public function redcap_every_page_top()
    {
        if ($this->isPage("ExternalModules/manager/project.php")) {
            $this->initSotProject();
            echo "<script src='{$this->getUrl('config.js')}'> </script>";
        }
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
        $payload = $request->getRequestVars();
        $project_id = $payload["projectid"] ?? $_GET["pid"];
        $err_msg = "Not supported";
        $result = null;

        // API calls need to have a new project instance created
        if (!isset($Proj)) {
            $Proj = new Project($project_id);
        }

        $funcs = [
            "availability" => [
                "create" => "setAvailability",
                "read" => "getAvailability",
                "update" => "",
                "delete" => ""
            ],
            "appointment" => [
                "create" => "setAppointments",
                "read" => "getAppointments",
                "update" => "",
                "delete" => ""
            ],
            "provider" => [
                "read" => "getProviders",
                "default" => "Provider resource is read only."
            ],
            "subject" => [
                "read" => "getSubjects",
                "default" => "Subject resource is read only."
            ],
            "location" => [
                "read" => "getLocations",
                "default" => "Location resource is read only."
            ]
        ];

        $task = $funcs[$payload["resource"]][$payload["crud"]];
        if (!empty($task)) {
            $result = $this->$task($payload);
        } else {
            $err_msg = $funcs[$payload["resource"]]["default"] ?? $err_msg;
        }

        if ($result) {
            // Fire DET at the end
            if ($this->getProjectSetting('fire-det') && in_array($payload["action"], ["create", "update", "delete"])) {
                $this->fireDataEntryTrigger($payload);
            }
            return json_encode($result);
        }

        RestUtility::sendResponse(400, $err_msg);
    }

    /*
    Get all providers that exist in both Project and SOT.
    */
    private function getProviders($payload = Null)
    {
        $isSot = $this->getProjectSetting("is-sot");
        $sot = $isSot ? Null : $this->getProjectSetting("source-of-truth");
        $sotProviders = REDCap::getData($sot, "array", Null, ["record_id", "name"]);
        $localProviders = REDCap::getUsers();
        $providers = [];

        foreach ($localProviders as $local) {
            if (array_key_exists($local, $sotProviders)) {
                $name = reset($sotProviders[$local])["name"];
                $providers[$local] = [
                    "value" => $local,
                    "label" => $name ?? $local,
                    "customProperties" => [
                        "username" => $local,
                        "name" => $name
                    ]
                ];
            }
        }

        return $providers;
    }

    /*
    Get all subjects that exist in the current project or 
    all subjects that have an appointment with the given 
    provider (for My Calendar page)
    */
    private function getSubjects($payload = Null)
    {
        $provider = !empty($payload) ? $payload["provider"] : Null;
        $isSot = $this->getProjectSetting("is-sot");
        $nameField = $this->getProjectSetting("name-field");
        $locationField = $this->getProjectSetting("location-field");
        $withdrawField = $this->getProjectSetting("withdraw-field");
        $result = [];

        if (!empty($provider)) {
            $sot = $this->getProjectSetting("source-of-truth");
            $sotData = reset(REDCap::getData($sot, "array", $provider, ["scheduled"])[$provider]);
            $pullData = [];
            foreach ($sotData as $instance => $monthData) {
                $monthData = json_decode($monthData["scheduled"] ?? [], true);
                foreach ($monthData as $time => $schData) {
                    if ($schData["provider"] == $provider) {
                        $record_id = $schData["subject"];
                        $pid = $schData["pid"];
                        $pullData[$pid][] = $record_id;
                    }
                }
            }
            foreach ($pullData as $pid => $records) {
                $nameField = $this->getProjectSetting("name-field");
                $projectData = $this->getSingleEventFields([$nameField], $records, $pid);
                foreach ($projectData as $record_id => $record_data) {
                    $name = $record_data[$nameField];
                    $result["$pid:$record_id"] = [
                        "value" => $record_id,
                        "label" => $name ?? $record_id,
                        "customProperties" => [
                            "location" => null,
                            "name" => $name,
                            "record_id" => $record_id
                        ]
                    ];
                }
            }
        }

        if (!$isSot && empty($provider)) {
            $data = $this->getSingleEventFields([$nameField, $locationField, $withdrawField]);
            foreach ($data as $record_id => $recordData) {
                $name = $recordData[$nameField];
                $loc = $recordData[$locationField];
                $withdraw = $recordData[$withdrawField];
                if ($withdraw) continue;
                $result[$record_id] = [
                    "value" => $record_id,
                    "label" => $name ?? $record_id,
                    "customProperties" => [
                        "location" => $loc,
                        "name" => $name,
                        "record_id" => $record_id
                    ]
                ];
            }
        }

        return $result;
    }

    private function getLocations($payload = Null)
    {
        $isSot = $this->getProjectSetting("is-sot");
        $sot = $isSot ? Null : $this->getProjectSetting("source-of-truth");
        $locations = $this->getProjectSetting("locations-json", $sot);
        $locations = json_decode($locations, true) ?? [];
        return $locations;
    }

    private function getAvailability($payload = Null)
    {
        return [[
            "title" => "test thing",
            "start" => date("Y-m-d") . "T11:00",
            "end" =>  date("Y-m-d") . "T13:00"
        ]];
    }

    private function setAvailability($payload = Null)
    {
        return [[
            "title" => "test thing",
            "start" => date("Y-m-d") . "T11:00",
            "end" =>  date("Y-m-d") . "T13:00"
        ]];
    }

    private function getAppointments($payload = Null)
    {
        return [[
            "title" => "test thing",
            "start" => date("Y-m-d") . "T11:00",
            "end" =>  date("Y-m-d") . "T13:00"
        ]];
    }

    private function setAppointments($payload = Null)
    {
        return [[
            "title" => "test thing",
            "start" => date("Y-m-d") . "T11:00",
            "end" =>  date("Y-m-d") . "T13:00"
        ]];
    }

    private function fireDataEntryTrigger($saveParams)
    {
        // Chunks of this function are lifted from the DataEntry class
        global $data_entry_trigger_url, $data_entry_trigger_enabled;
        $longitudinal = REDCap::isLongitudinal();

        // Check if enabled
        if (!$data_entry_trigger_enabled || $data_entry_trigger_url == '') {
            return false;
        }

        // Build HTTP Post request parameters to send
        $params = array(
            'redcap_url' => APP_PATH_WEBROOT_FULL,
            'project_url' => APP_PATH_WEBROOT_FULL . "redcap_v" . REDCAP_VERSION . "/index.php?pid=" . PROJECT_ID,
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

    private function getSingleEventFields($fields, $records = Null, $project_id = Null)
    {
        if ($project_id == Null) {
            $project_id = $_GET["pid"] ?? PROJECT_ID;
        }
        $fieldClause = "\"" . implode("\",\"", $fields) . "\"";
        $recordClause = !empty($records) ? "AND record IN \"" . implode("\",\"", $records) . "\"" : "AND";
        $sql = "SELECT record, field_name, `value` 
        FROM redcap_data 
        WHERE project_id = $project_id AND field_name IN ($fieldClause) $recordClause event_id IN (
            SELECT event_id 
            FROM redcap_events_forms 
            WHERE form_name IN (
                SELECT form_name 
                FROM redcap_metadata 
                WHERE project_id = $project_id AND field_name IN ($fieldClause)));";
        $data = db_query($sql);
        $results = [];
        while ($row = db_fetch_assoc($data)) {
            $results[$row["record"]][$row["field_name"]] = $row["value"];
        }
        return $results;
    }
}
