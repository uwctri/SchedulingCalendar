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

    public function currentUser()
    {
        $admins = $this->getProjectSetting("calendar-admin")[0];
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
        $provider = !empty($payload) ? $payload["provider"] : Null;
        $nameField = $this->getProjectSetting("name-field");
        $locationField = $this->getProjectSetting("location-field");
        $withdrawField = $this->getProjectSetting("withdraw-field");
        $result = [];

        if (!empty($provider)) {
            $sql = $this->query("SELECT * FROM em_scheduling_calendar WHERE user = '?'", $provider);

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
        $start = str_replace("T", " ", $payload["start"]);
        $end = str_replace("T", " ", $payload["end"]);
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
                "title" => "$codeName<br>$provider<br>$location",
                "start" => str_replace(' ', 'T', $row["time_start"]),
                "end" => str_replace(' ', 'T', $row["time_end"]),
                "location" => $row["location"],
                "user" => $row["user"],
                "availability_code" => $row["availability_code"],
                "metadata" => json_decode($row["metadata"], true) ?? []
            ];
        }

        return $availability;
    }

    private function setAvailability($payload = Null)
    {
        $project_id = $payload["pid"];
        $code = $payload["group"];
        $start = str_replace("T", " ", $payload["start"]);
        $end = str_replace("T", " ", $payload["end"]);
        $user = $payload["provider"];
        $location = $payload["location"];

        $start_of_day = substr($start, 0, 10) . " 00:00";
        $end_of_day = substr($end, 0, 10) . " 23:59";
        $existing = $this->getAvailability([
            "providers" => $user,
            "locations" => $location,
            "start" => $start_of_day,
            "end" => $end_of_day
        ]);

        $resolved = false;
        if (!empty($existing)) {
            foreach ($existing as $appt) {
                if ($resolved) break;
                $id = $appt["internal_id"];
                $apptStart = str_replace("T", " ", $appt["start"]);
                $apptEnd = str_replace("T", " ", $appt["end"]);
                if ($apptStart <= $start && $apptEnd >= $end) {
                    // Skip creation, its a duplicate
                    $resolved = true;
                }
                if ($apptStart <= $start && $end > $apptEnd) {
                    // Extend the end of the existing appointment
                    $resolved = true;
                    $this->modifyAvailabiltiy($id, null, $end);
                }
                if ($start < $apptStart && $apptEnd >= $end) {
                    // Extend the start of the existing appointment (to earlier in the day)
                    $resolved = true;
                    $this->modifyAvailabiltiy($id, $start, null);
                }
                if ($start < $apptStart && $end > $apptEnd) {
                    // Extend the start and end of the existing appointment
                    $resolved = true;
                    $this->modifyAvailabiltiy($id, $start, $end);
                }
            }
        }

        if (!$resolved) {
            $this->query(
                "INSERT INTO em_scheduling_calendar (project_id, availability_code, user, location, time_start, time_end) VALUES (?, ?, ?, ?, ?, ?)",
                [$project_id, $code, $user, $location, $start, $end]
            );
        }
        return $existing;
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
