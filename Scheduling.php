<?php

namespace UWMadison\Scheduling;

use ExternalModules\AbstractExternalModule;
use REDCap;
use RestUtility;
use Project;

class Scheduling extends AbstractExternalModule
{
    public function redcap_module_system_enable()
    {
        db_query("CREATE TABLE IF NOT EXISTS `em_scheduling_calendar` (
            `id` INT AUTO_INCREMENT,
            `project_id` INT,
            `user` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
            `record` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
            `location` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
            `time_start` timestamp NOT NULL,
            `time_end` timestamp NOT NULL,
            `metadata` JSON,
            PRIMARY KEY (`id`)
        );");
    }

    public function redcap_every_page_top($project_id)
    {
        if ($this->isPage("ExternalModules/manager/project.php") && $project_id) {
            echo "<script src='{$this->getUrl('config.js')}'> </script>";
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

        // Fire DET at the end
        if ($this->getProjectSetting('fire-det') && in_array($payload["action"], ["create", "update", "delete"])) {
            $this->fireDataEntryTrigger($payload);
        }
        return json_encode($result);
    }

    /*
    Get all providers that exist in the project or any other
    */
    private function getProviders($payload = Null)
    {
        // Get users that have been used the EM
        $sql = $this->query("SELECT DISTINCT user FROM redcap.em_scheduling_calendar", []);
        $globalProviders = [];
        while ($row = db_fetch_assoc($sql)) {
            $globalProviders[] = $row["user"];
        }

        // Get all local users
        $localProviders = REDCap::getUsers();

        // Get all user info for the RC instance
        $allUsers = $this->getAllUsers();

        // Loop over all usernames and reformat them
        $unformatted = array_merge($globalProviders, $localProviders);
        $providers = [];
        foreach ($unformatted as $username) {
            if (array_key_exists($username, $allUsers)) {
                $name = $allUsers[$username] ?? "";
                $providers[$username] = [
                    "value" => $username,
                    "label" => $name ?? $username,
                    "customProperties" => [
                        "username" => $username,
                        "name" => $name
                    ]
                ];
            }
        }

        return $providers;
    }


    /*
    Get all users in the redcap instance
    */
    private function getAllUsers()
    {
        $users = [];
        $sql = $this->query("SELECT username, CONCAT(user_firstname, ' ' ,user_lastname) AS displayname FROM redcap_user_information", []);
        while ($row = db_fetch_assoc($sql)) {
            $users[$row["username"]] = $row["displayname"];
        }
        return $users;
    }

    /*
    Get all subjects that exist in the current project or 
    all subjects that have an appointment with the given 
    provider (for My Calendar page)
    */
    private function getSubjects($payload = Null)
    {
        $provider = !empty($payload) ? $payload["provider"] : Null;
        $nameField = $this->getProjectSetting("name-field");
        $locationField = $this->getProjectSetting("location-field");
        $withdrawField = $this->getProjectSetting("withdraw-field");
        $result = [];

        if (!empty($provider)) {
            $sql = $this->query("SELECT * FROM redcap.em_scheduling_calendar WHERE user = '?'", [$provider]);

            $data = [];
            while ($row = db_fetch_assoc($sql)) {
                $data[$row["project_id"]][$row["record"]] = $row["location"];
            }
            foreach ($data as $pid => $records) {
                $nameField = $this->getProjectSetting($pid, "name-field");
                $projectData = $this->getSingleEventFields([$nameField], $records, $pid);
                foreach ($projectData as $record_id => $record_data) {
                    $loc = $records[$record_id];
                    $name = $record_data[$nameField];
                    $result["$pid:$record_id"] = [
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
        }

        if (empty($provider)) {
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
        $locations = $this->getSystemSetting("locations-json");
        $locations = json_decode($locations, true) ?? [];
        return $locations;
    }

    private function getAvailability($payload = Null)
    {
        return [[
            "title" => "test thing",
            "start" => date("Y-m-d") . "T11:00",
            "end" =>  date("Y-m-d") . "T13:00",
            "location" => "test location"
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
        $records = empty($records) ? $records : array_keys($records);
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
