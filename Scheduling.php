<?php

namespace UWMadison\Scheduling;

use ExternalModules\AbstractExternalModule;
use REDCap;
use RestUtility;
use Project;

class Scheduling extends AbstractExternalModule
{

    /*
    Create the core scheduling and availability table on module enable
    */
    public function redcap_module_system_enable()
    {
        // 
        db_query("CREATE TABLE IF NOT EXISTS em_scheduling_calendar (
            `id` INT AUTO_INCREMENT,
            `project_id` INT,
            `event_id` INT,
            `availability_code` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
            `user` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
            `record` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
            `location` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
            `time_start` timestamp NOT NULL,
            `time_end` timestamp NOT NULL,
            `metadata` JSON,
            PRIMARY KEY (`id`)
        );");
    }

    /*
    Load some config JS on the settings page
    */
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

        // TODO we aren't ever returning errors right now
        $request = RestUtility::processRequest($tokenRequired);
        $payload = $request->getRequestVars();
        $project_id = $payload["projectid"] ?? $_GET["pid"];
        $err_msg = "Not supported";
        $result = null;

        // API calls need to have a new project instance created
        if (!isset($Proj)) {
            $Proj = new Project($project_id);
        }

        // Grab start/end from GET for full calendar
        $payload["start"] = $_GET["start"] ?? $payload["start"];
        $payload["end"] = $_GET["end"] ?? $payload["end"];
        $payload["pid"] = $project_id;

        $funcs = [
            "availabilitycode" => [
                "read" => "getAvailabilityCodes",
                "default" => "Availability Code resource is read only"
            ],
            "availability" => [
                "create" => "setAvailability",
                "read" => "getAvailability",
                "update" => "",
                "delete" => "deleteEntry"
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
            $err_msg = "";
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
    Get info on the current user
    */
    public function currentUser()
    {
        $admins = $this->getProjectSetting("calendar-admin");
        $user = $this->getUser();
        $username = $user->getUsername();
        return [
            "username" => $username,
            "email" => $user->getEmail(),
            "name" => $GLOBALS['user_firstname'] . ' ' . $GLOBALS['user_lastname'],
            "isCalendarAdmin" => in_array($username, $admins),
            "isSuperUser" => $user->isSuperUser()
        ];
    }

    /*
    Get all providers that exist in the project or any other
    */
    private function getProviders($payload = Null)
    {
        // Get users that have been used the EM
        $noParams = [];
        $sql = $this->query("SELECT DISTINCT user FROM em_scheduling_calendar", $noParams);
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
        $noParams = [];
        $sql = $this->query("SELECT username, CONCAT(user_firstname, ' ' ,user_lastname) AS displayname FROM redcap_user_information", $noParams);
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
        $providers = $payload["providers"];
        $nameField = $this->getProjectSetting("name-field");
        $locationField = $this->getProjectSetting("location-field");
        $withdrawField = $this->getProjectSetting("withdraw-field");
        $result = [];

        if (empty($nameField)) {
            return [];
        }

        if (!empty($providers)) {
            $sql = $this->query("SELECT * FROM em_scheduling_calendar WHERE user = '?'", $providers);

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

        if (empty($providers)) {
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
        return $this->getLocationStructure();
    }

    private function getLocationStructure($flatten = false)
    {
        $locations = $this->getSystemSetting("locations-json");
        $locations = json_decode($locations, true) ?? [];
        if ($flatten) {
            $flat_locations = [];
            foreach ($locations as $code => $data) {
                $sites = $data["sites"];
                unset($data["sites"]);
                $flat_locations[$code] = $data;
                foreach ($sites as $site_code => $site) {
                    $flat_locations[$site_code] = $site;
                }
            }
            return $flat_locations;
        }
        return $locations;
    }

    private function getAvailabilityCodes($payload = Null)
    {
        $displayNames = $this->getSystemSetting("group-name");
        $codedValues = $this->getSystemSetting("group-code");
        $result = array_combine($codedValues, $displayNames);
        foreach ($result as $code => $name) {
            $result[$code] = [
                "value" => $code,
                "label" => $name
            ];
        }
        return $result;
    }

    private function getAvailability($payload = Null)
    {
        // TODO for editing availability we would ignore the codes and just get all availability
        // TODO for editing availability we will want to show the code in the title
        $availability = [];
        $providers = $payload["providers"];
        $locations = $payload["locations"];
        $start = $payload["start"];
        $end = $payload["end"];
        // Filtering by event_id and record aren't a thing for Availability
        $codes = explode(',', $this->getProjectSetting("availability-codes"));
        if (empty($codes)) {
            return $availability;
        }

        $allUsers = $this->getAllUsers();
        $allLocations = $this->getLocationStructure(true);

        $query = $this->createQuery();
        $query->add("SELECT id, record, event_id, availability_code, user, location, time_start, time_end, metadata FROM em_scheduling_calendar");
        $query->add("WHERE");
        $query->addInClause("availability_code", $codes);

        if (!empty($providers)) {
            $query->add("AND")->addInClause("user", $providers);
        }

        if (!empty($locations)) {
            $query->add("AND")->addInClause("location", $locations);
        }

        $query->add("AND time_start >= ? AND time_end <= ?", [$start, $end]);

        $codes = $this->getAvailabilityCodes();
        $result = $query->execute();
        while ($row = $result->fetch_assoc()) {
            $provider = $allUsers[$row["user"]] ?? $row["user"];
            $location = $allLocations[$row["location"]]["name"] ?? $row["location"];
            $codeName = $codes[$row["availability_code"]]["label"] ?? $row["availability_code"];
            $availability[] = [
                "internal_id" => $row["id"],
                "title" => "Default Title",
                "start" => $row["time_start"],
                "end" => $row["time_end"],
                "location" => $row["location"],
                "location_display" => $location,
                "user" => $row["user"],
                "user_display" => $provider,
                "availability_code" => $row["availability_code"],
                "availability_code_display" => $codeName,
                "metadata" => json_decode($row["metadata"], true) ?? [],
                "is_availability" => true,
                "is_appointment" => false
            ];
        }

        return $availability;
    }

    private function setAvailability($payload = Null)
    {
        $project_id = $payload["pid"];
        $code = $payload["group"];
        $start = $payload["start"];
        $end = $payload["end"];
        $provider = $payload["provider"];
        $location = $payload["location"];
        $dateStr = substr($start, 0, 10);

        $mergeOccured = $this->cleanupAvailabiltiy($dateStr, $provider, $location, $code, null, [
            "start" => $start,
            "end" => $end
        ]);

        if (!$mergeOccured['bool']) {
            $this->query(
                "INSERT INTO em_scheduling_calendar (project_id, availability_code, user, location, time_start, time_end) VALUES (?, ?, ?, ?, ?, ?)",
                [$project_id, $code, $provider, $location, $start, $end]
            );
        }
        return [$mergeOccured];
    }

    private function cleanupAvailabiltiy($dateStr, $provider, $location, $code, $existing = null, $working = null)
    {
        $start_of_day = $dateStr . " 00:00";
        $end_of_day = $dateStr . " 23:59";

        // If this is the first call get the existing availability
        if ($existing == null) {
            $existing = $this->getAvailability([
                "providers" => $provider,
                "locations" => $location,
                "start" => $start_of_day,
                "end" => $end_of_day
            ]);

            // Filter to those with correct code
            $existing = array_filter($existing, function ($x) use ($code) {
                return $x["availability_code"] == $code;
            });
        }

        // Working is only passed in when we are working with availability that
        // has not been saved yet
        $performDelete = false;
        if ($working == null) {
            // Nothing to merge
            if (count($existing) < 2)
                return ['bool' => false, 'existing' => $existing, 'working' => $working, 'msg' => '1'];

            $performDelete = true;
            $working = array_pop($existing);
        } elseif (count($existing) == 0) {
            // Nothing to merge
            return ['bool' => false, 'existing' => $existing, 'working' => $working, 'msg' => '2'];
        }

        $start = $working["start"];
        $end = $working["end"];
        $resolved = false;
        foreach ($existing as $appt) {
            if ($resolved)
                break;

            $id = $appt["internal_id"];
            $apptStart = $appt["start"];
            $apptEnd = $appt["end"];

            if (($apptStart <= $start) && ($apptEnd >= $end)) {
                // Skip creation, its a duplicate
                $resolved = true;
            }
            if (($apptStart <= $start) && ($end > $apptEnd) && ($start <= $apptEnd)) {
                // Extend the end of the existing appointment
                $resolved = true;
                $this->modifyAvailabiltiy($id, null, $end);
            }
            if (($start < $apptStart) && ($apptEnd >= $end) && ($end >= $apptStart)) {
                // Extend the start of the existing appointment (to earlier in the day)
                $resolved = true;
                $this->modifyAvailabiltiy($id, $start, null);
            }
            if (($start < $apptStart) && ($end > $apptEnd)) {
                // Extend the start and end of the existing appointment
                $resolved = true;
                $this->modifyAvailabiltiy($id, $start, $end);
            }
        }

        // Some merge occured, delete the working availability
        if ($resolved && $performDelete) {
            $this->deleteEntry($working["internal_id"]);
        }

        // Merge occured, attempt again
        if ($resolved) {
            $this->cleanupAvailabiltiy($dateStr, $provider, $location, $code);
            return ['bool' => true, 'existing' => $existing, 'working' => $working, 'msg' => '4'];
        }

        // Nothing was merged
        return ['bool' => false, 'existing' => $existing, 'working' => $working, 'msg' => '3'];
    }

    private function deleteEntry($id)
    {
        if (is_array($id)) {
            $id = $id["internal_id"] ?? $id["id"];
        }
        $this->query("DELETE FROM em_scheduling_calendar WHERE id = ?", [$id]);
        return [];
    }

    private function modifyAvailabiltiy($id, $newStart = null, $newEnd = null)
    {
        $query = $this->createQuery();
        $query->add("UPDATE em_scheduling_calendar SET");
        $conditions = [];
        if ($newStart != null)
            $conditions[] = "time_start = ?";
        if ($newEnd != null)
            $conditions[] = "time_end = ?";
        $params = array_filter([$newStart, $newEnd]);
        $query->add(implode(',', $conditions), $params);
        $query->add("WHERE id = ?", [$id]);
        $query->execute();
        return [];
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
        $fields = array_filter($fields);
        $data = REDCap::getData($project_id, 'array', $records, $fields);
        $results = [];
        foreach ($data as $record_id => $event_data) {
            foreach ($event_data as $event_id => $fields) {
                foreach ($fields as $field => $value) {
                    $results[$record_id][$field] = $value;
                }
            }
        }
        return $results;
    }
}
