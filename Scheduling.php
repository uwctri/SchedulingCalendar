<?php

namespace UWMadison\Scheduling;

use ExternalModules\AbstractExternalModule;
use REDCap;
use RestUtility;

class Scheduling extends AbstractExternalModule
{
    private $schema = null; // API Schema

    /*
    Create the core scheduling and availability table on module enable
    */
    public function redcap_module_system_enable()
    {
        db_query("CREATE TABLE IF NOT EXISTS em_scheduling_calendar (
            `id` INT AUTO_INCREMENT,
            `project_id` INT,
            `visit` VARCHAR(126),
            `availability_code` VARCHAR(126) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
            `user` VARCHAR(126) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
            `record` VARCHAR(126) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
            `location` VARCHAR(126) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
            `time_start` timestamp DEFAULT 0, 
            `time_end` timestamp DEFAULT 0, 
            `notes` TEXT, 
            `metadata` JSON,
            PRIMARY KEY (`id`)
        );");
        // Note: timestamps must be defaulted as we don't know the value of explicit_defaults_for_timestamp
        // w/o a DEFAULT or ON UPDATE clause the timestamp will default to CURRENT_TIMESTAMP
    }

    /*
    Load some config JS on the settings page
    */
    public function redcap_every_page_top($project_id)
    {
        if ($this->isPage("ExternalModules/manager/project.php") && $project_id)
            echo "<link rel='stylesheet' href='{$this->getUrl('style.css')}'>";
    }

    /*
    Always show the page link
    */
    public function redcap_module_link_check_display($project_id, $link)
    {
        return $link;
    }

    /*
    Cache the API schema from JSON when it is requested
    */
    public function getSchema()
    {
        if ($this->schema == null)
            $this->schema = json_decode(file_get_contents($this->getUrl("schema.json")), true);
        return $this->schema;
    }

    /*
    Process a post request from router
    */
    public function process()
    {
        $request = RestUtility::processRequest(false);
        $payload = $request->getRequestVars();
        $project_id = $payload["projectid"] ?? $this->escape($_GET["pid"]);
        $payload["pid"] = $project_id;
        $err_msg = "Not supported. Invalid resource or CRUD operation.";
        $result = null;

        // Replace placeholders for empty arrays
        $payload = array_map(function ($x) {
            return $x == "[]" ? [] : $x;
        }, $payload);

        // Check if its the non-CRUD utility function
        if (!empty($payload["utility"]) && $payload["utility"] == "ics") {
            $result = [
                "data" => $this->makeICS($payload),
                "success" => true
            ];
            return json_encode($result);
        }

        // Check Schema if any
        $schema = $this->getSchema()[$payload["resource"]][$payload["crud"]];
        if ($schema) {
            $schemaError = true;
            foreach ($schema as $schemOption)
                if (count(array_intersect_key(array_flip($schemOption), $payload)) === count($schemOption))
                    $schemaError = false; // All required keys are present
        }

        // CRUD functions
        $task = [
            "availabilitycode" => [
                "read" => "getAvailabilityCodes",
                "default" => "Availability Code resource is read only"
            ],
            "availability" => [
                "create" => "setAvailability",
                "read" => "getAvailability",
                "update" => "modifyAvailability",
                "delete" => "deleteAvailability",
            ],
            "appointment" => [
                "create" => "setAppointments",
                "read" => "getAppointments",
                "update" => "modifyAppointments",
                "delete" => "deleteAppointments"
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
            ],
            "visit" => [
                "read" => "getVisits",
                "default" => "Vist resource is read only."
            ],
            "metadata" => [
                "read" => "getUserMetadata",
                "update" => "setUserMetadata",
                "default" => "Metadata resource can be read and updated only."
            ],
        ][$payload["resource"]][$payload["crud"]];

        if ($schemaError) {
            $err_msg = "Missing parameters for operation";
        } elseif ($payload["bundle"] && !empty($task)) {
            $result = [];
            foreach ($payload["bundle"] as $subPayload) {
                $subPayload["pid"] = $project_id;
                $result[] = $this->$task($subPayload);
            }
        } elseif (!empty($task)) {
            $err_msg = "";
            $result = $this->$task($payload);
        } else {
            $err_msg = $funcs[$payload["resource"]]["default"] ?? $err_msg;
        }

        // Fire DET at the end
        if ($this->getProjectSetting('fire-det') && in_array($payload["crud"], ["create", "update", "delete"])) {
            $this->fireDataEntryTrigger($payload);
        }

        // Return the error or result
        return $err_msg ? json_encode([
            "success" => false,
            "msg" => $err_msg,
            "payload" => $payload
        ]) : json_encode($result);
    }

    /*
    Get info on the current user
    */
    public function currentUser()
    {
        $admins = $this->getProjectSetting("calendar-admin");
        $user = $this->getUser();
        $username = $user->getUsername();
        $isAdmin = in_array($username, $admins);
        $hash = "";
        if ($isAdmin) {
            $json = json_decode($this->getProjectSetting("ics-hash-json") ?? "{}", true);
            if (in_array($username, array_values($json))) {
                $hash = array_search($username, $json);
            } else {
                $hash = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(128))), 0, 128);
                $json[$hash] = $username;
                $this->setProjectSetting("ics-hash-json", json_encode($json));
            }
        }
        return [
            "username" => $username,
            "email" => $user->getEmail(),
            "name" => $GLOBALS['user_firstname'] . ' ' . $GLOBALS['user_lastname'],
            "isCalendarAdmin" => $isAdmin,
            "isSuperUser" => $user->isSuperUser(),
            "icsHash" => $hash
        ];
    }

    public function getProjectName($project_id = null)
    {
        $project_id = $project_id ?? $this->getProjectId();
        $sql = $this->query("SELECT app_title FROM redcap_projects WHERE project_id = ?", [$project_id]);
        return $this->escape(db_fetch_assoc($sql)["app_title"]);
    }

    public function getContactEmail()
    {
        $sql = $this->query("SELECT value FROM redcap_config WHERE field_name = 'homepage_contact_email'", []);
        return $this->escape(db_fetch_assoc($sql)["value"]);
    }

    /*
    Get all providers that exist in the project or any other
    */
    private function getProviders($payload = null)
    {
        // Get users that have used the EM
        $noParams = [];
        $sql = $this->query("SELECT DISTINCT user FROM em_scheduling_calendar", $noParams);
        $globalProviders = [];
        while ($row = db_fetch_assoc($sql)) {
            $globalProviders[] = $row["user"];
        }

        // Get all local users & Settings
        $localProviders = REDCap::getUsers();
        $unschedulables = $this->getProjectSetting("unschedulable");
        $admins = $this->getProjectSetting("calendar-admin");

        // Get all user info for the RC instance
        $allUsers = $this->getAllUsers();

        // Loop over all usernames and reformat them
        $unformatted = array_merge($globalProviders, $localProviders);
        $providers = [];
        foreach ($unformatted as $username) {
            if (array_key_exists($username, $allUsers)) {
                $name = $allUsers[$username];
                $providers[$username] = [
                    "value" => $username,
                    "label" => $name ?? $username,
                    "username" => $username,
                    "name" => $name ?? $username,
                    "is_unschedulable" => in_array($username, $unschedulables),
                    "is_admin" => in_array($username, $admins),
                    "is_local" => in_array($username, $localProviders)
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
    Get all subjects that exist in the current project
    */
    private function getSubjects($payload)
    {
        $project_id = $payload["pid"];
        $nameField = $this->getProjectSetting("name-field");
        $subjects = [];

        if (empty($nameField))
            return [];

        // Used for all subjects on a project
        $locDefault = $this->getProjectSetting("location-default", $project_id);
        $locationField = $this->getProjectSetting("location-field", $project_id);
        $locationStatic = ""; // Blank
        if ($locDefault == "static") {
            $locationField = null;
            $locationStatic = $this->getProjectSetting("location-static", $project_id);
        } elseif ($locDefault == "blank" || empty($locDefault)) {
            $locationField = null;
        }

        // Get all data we need to pull together
        $visitSettings = $this->getVisits($payload, true);
        $blFields = $this->getProjectSetting("visit-branch-logic-field");
        $allData = REDCap::getData($project_id, "array", null, array_merge($blFields, [$visitSettings["rangeStart"], $visitSettings["rangeEnd"]]));
        $withdrawField = $this->getProjectSetting("withdraw-field", $project_id);
        $data = $this->getSingleEventFields([$nameField, $locationField, $withdrawField], null, $project_id);

        // Loop over every record and build out info
        foreach ($data as $record_id => $recordData) {
            $name = $recordData[$nameField];
            $loc = $recordData[$locationField] ?? $locationStatic;
            $withdraw = boolval($recordData[$withdrawField]);
            $subjects[$record_id] = [
                "value" => $record_id,
                "label" => $name ?: "$record_id",
                "location" => $loc,
                "name" => $name,
                "record_id" => $record_id,
                "is_withdrawn" => $withdraw,
                "project_id" => $project_id,
                "summary_fields" => [],
                "visits" => [
                    // "visit_code" = [
                    //     "branching_logic" => true,
                    //     "scheduled" => [],
                    //     "range" => []
                    // ];
                ]
            ];


            // Do Branching logic evaluation for every record
            $blData = $blFields ? $allData : [];
            foreach ($visitSettings["visits"] as $visit => $vSet) {
                $blValue = $vSet["blValue"];
                $blEvent = $vSet["blEvent"];
                $blField = $vSet["blField"];
                $subjects[$record_id]["visits"][$visit]["branching_logic"] = true;
                $subjects[$record_id]["visits"][$visit]["range"] = [];
                if ($blData && $blEvent && $blField) {
                    $not = (count($blValue) > 0) && ($blValue[0] == "!");
                    $v = $blData[$record_id][$blEvent][$blField];
                    $subjects[$record_id]["visits"][$visit]["branching_logic"] = ($v == ($not ? substr($blValue, 1) : $blValue));
                }
                if ($allData && $vSet["link"] && $visitSettings["rangeStart"] && $visitSettings["rangeEnd"]) {
                    $rangeStart = $allData[$record_id][$vSet["link"]][$visitSettings["rangeStart"]];
                    $rangeEnd = $allData[$record_id][$vSet["link"]][$visitSettings["rangeEnd"]];
                    $subjects[$record_id]["visits"][$visit]["range"] = [$rangeStart, $rangeEnd];
                }
            }
        }

        // Perform a second query to get all scheduled visits for the subjects
        $query = $this->createQuery();
        $query->add("SELECT record, visit, time_start from em_scheduling_calendar WHERE project_id = ?", $project_id);
        $query->add("AND")->addInClause("record", array_keys($subjects));
        $result = $query->execute();
        while ($row = $result->fetch_assoc()) {
            $record = $row["record"];
            $visit = $row["visit"];
            $subjects[$record]["visits"][$visit]["scheduled"][] = $row["time_start"];
        }

        // Check if any exta info is on the subject summary (3rd query)
        $extaFields = $this->getProjectSetting("ss-field");
        if (!empty($extaFields)) {
            $dd = Redcap::getDataDictionary($project_id, 'array', false, $extaFields);
            $eData = $this->getSingleEventFields($extaFields, null, $project_id);
            foreach ($eData as $record => $recordData) {
                foreach ($recordData as $field => $val) {
                    $subjects[$record]["summary_fields"][$field] = [
                        "value" => $val,
                        "label" => $dd[$field]["field_label"]
                    ];
                }
            }
        }

        return $subjects;
    }

    private function getGlobalSubjects($providers)
    {
        if (is_array($providers)) {
            if (count($providers) == 0)
                return [];
            $query = $this->createQuery();
            $query->add("SELECT * FROM em_scheduling_calendar WHERE record IS NOT NULL");
            $query->add("AND")->addInClause("user", $providers); // We don't use this right now, we only search for 1 provider
            $sql = $query->execute();
        } else {
            $sql = $this->query("SELECT * FROM em_scheduling_calendar WHERE user = ? AND record IS NOT NULL", [$providers]);
        }

        $data = [];
        $subjects = [];
        while ($row = db_fetch_assoc($sql)) {
            $data[$row["project_id"]][$row["record"]] = $row["location"];
        }
        foreach ($data as $pid => $records) {
            $nameField = $this->getProjectSetting("name-field", $pid);
            $projectData = $this->getSingleEventFields([$nameField], array_keys($records), $pid);
            foreach ($projectData as $record_id => $record_data) {
                $loc = $records[$record_id];
                $name = $record_data[$nameField];
                $subjects["$pid:$record_id"] = [
                    "value" => $record_id,
                    "label" => $name ?: "$record_id",
                    "location" => $loc,
                    "name" => $name,
                    "record_id" => $record_id,
                    "project_id" => $pid,
                    "is_withdrawn" => false // Always false for My Sched page
                ];
            }
        }

        return $subjects;
    }

    private function getLocations($payload = null)
    {
        return $this->getLocationStructure();
    }

    private function getLocationStructure($flatten = false)
    {
        $sot = $this->getProjectSetting("location-sot");
        $pid = $sot == "json" ? null : $this->getProjectSetting("location-pid");
        $locations = $this->getProjectSetting("location-json", $pid);
        $locations = json_decode($locations, true) ?? [];
        if ($flatten) {
            $flat_locations = [];
            foreach ($locations as $code => $data) {
                $sites = $data["sub"];
                unset($data["sub"]);
                $flat_locations[$code] = $data;
                foreach ($sites as $site_code => $site) {
                    $flat_locations[$site_code] = array_merge($site, ["parent" => $code]);
                }
            }
            return $flat_locations;
        }
        return $locations;
    }

    private function getAvailabilityCodes($payload = null)
    {
        $project_id = $payload["pid"];
        $globalFlag = $this->getSystemSetting("global-group") == "1";
        $localFlag = !$this->getSystemSetting("no-local-group") == "1";
        $systemIndex = array_search($project_id, $this->getSystemSetting("availability-pid") ?? []);
        $localCodes = [];
        if ($systemIndex !== false)
            $localCodes = array_map('trim', explode(',', $this->getSystemSetting("availability-codes")[$systemIndex]));
        $allFlag = $payload["all_availability"]; // Defaults to false when payload is null
        $allCodes = array_combine($this->getSystemSetting("group-code"), $this->getSystemSetting("group-name"));
        $result = [];
        if ($globalFlag) {
            $result["global"] = [
                "value" => "global",
                "label" => "Global",
                "isLocal" => false
            ];
        }
        if ($localFlag) {
            $result[$project_id] = [
                "value" => $project_id,
                "label" => "This Project",
                "isLocal" => true
            ];
        }
        foreach ($allCodes as $code => $name) {
            $isLocal = in_array($code, $localCodes);
            if ($allFlag || $isLocal) {
                $result[$code] = [
                    "value" => $code,
                    "label" => $name,
                    "isLocal" => $isLocal
                ];
            }
        }
        return $result;
    }

    private function getVisits($payload, $includeSharedConfig = false)
    {
        $project_id = $payload["pid"];
        $names = [
            "display-name" => "label",
            "code" => "code",
            "linked-event" => "link",
            "notes" => "notes",
            "branch-logic-event" => "blEvent",
            "branch-logic-field" => "blField",
            "branch-logic-value" => "blValue",
            "duration" => "duration",
            "extendable" => "isExtendable",
            "location-free" => "isLocationFree",
        ];

        $values = array_map(function ($setting) use ($project_id) {
            return $this->getProjectSetting("visit-$setting", $project_id);
        }, array_keys($names));

        $visits = [];
        for ($i = 0; $i < count($values[0]); $i++) {
            $tmp = array_combine(array_values($names), array_column($values, $i));
            $tmp["value"] = $tmp["code"]; // Duplicate one item
            $visits[$tmp["code"]] = $tmp;
        }

        if ($includeSharedConfig) {
            $visits = [
                "visits" => $visits,
                "wbDateTimes" => $this->getProjectSetting("wb-datetime"),
                "wbUser" => $this->getProjectSetting("wb-user"),
                "rangeStart" => $this->getProjectSetting("range-start"),
                "rangeEnd" => $this->getProjectSetting("range-end"),
            ];
        }

        return $visits;
    }

    private function getAvailability($payload)
    {
        $availability = [];
        $providers = $payload["providers"];
        $locations = $payload["locations"];
        $start = $payload["start"];
        $end = $payload["end"];
        $allFlag = $payload["all_availability"];
        $overflowFlag = $payload["allow_overflow"]; // Internal param for scheduling

        $codes = $this->getAvailabilityCodes($payload);
        $codes_keys = array_keys($codes);
        if (empty($codes_keys) && !$allFlag) {
            return $availability;
        }

        $allUsers = $this->getAllUsers();
        $allLocations = $this->getLocationStructure(true);

        $query = $this->createQuery();
        $query->add("SELECT * FROM em_scheduling_calendar WHERE record IS NULL");

        if (!$allFlag) {
            $query->add("AND")->addInClause("availability_code", $codes_keys);
        }

        if (!empty($providers)) {
            $query->add("AND")->addInClause("user", $providers);
        }

        if (!empty($locations)) {
            $query->add("AND")->addInClause("location", $locations);
        }

        if (!$overflowFlag) {
            $query->add("AND time_start >= ? AND time_end <= ?", [$start, $end]);
        } else {
            $query->add("AND time_start <= ? AND time_end >= ?", [$start, $end]);
        }

        $result = $query->execute();
        while ($row = $result->fetch_assoc()) {
            $provider = $allUsers[$row["user"]] ?? $row["user"];
            $location = $allLocations[$row["location"]]["name"] ?? $row["location"];
            $codeName = $codes[$row["availability_code"]]["label"] ?? $row["availability_code"];
            $availability[] = [
                "internal_id" => $row["id"],
                "project_id" => $row["pid"],
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

    private function setAvailability($payload)
    {
        $project_id = $payload["pid"];
        $code = $payload["group"];
        $start = $payload["start"];
        $end = $payload["end"];
        $provider = $payload["providers"];
        $location = $payload["locations"];
        $dateStr = substr($start, 0, 10);

        $msg = "Modified existing availability";
        $mergeOccured = $this->cleanupAvailabiltiy($project_id, $dateStr, $provider, $location, $code, null, [
            "start" => $start,
            "end" => $end
        ]);

        if (!$mergeOccured) {
            $msg = "Inserted new availability";
            $this->query(
                "INSERT INTO em_scheduling_calendar (project_id, availability_code, user, location, time_start, time_end) VALUES (?, ?, ?, ?, ?, ?)",
                [$project_id, $code, $provider, $location, $start, $end]
            );
        }

        // Regardless of merge, review all appts for today
        // and delete any avaialbility that overlaps
        // foreach($this->getAppointments($payload) as $appt) {
        //     // TODO
        // }


        // Pull appoinments for the day
        // Use the snippet from setAppointments to clear the availability under the appts

        $this->log(
            "Availability Added" . ($mergeOccured ? " (merged with existing availability)" : ""),
            [
                "agent" => $this->getUser()->getUsername(),
                "provider" => $provider,
                "location" => $location,
                "start" => $start,
                "end" => $end,
                "code" => $code
            ]
        );

        return [
            "msg" => $msg,
            "success" => true
        ];
    }

    private function cleanupAvailabiltiy($project_id, $dateStr, $provider, $location, $code, $existing = null, $working = null)
    {
        $start_of_day = $dateStr . " 00:00";
        $end_of_day = $dateStr . " 23:59";

        // If this is the first call get the existing availability
        if ($existing == null) {
            $existing = $this->getAvailability([
                "pid" => $project_id,
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
                return false;

            $performDelete = true;
            $working = array_pop($existing);
        } elseif (count($existing) == 0) {
            // Nothing to merge
            return false;
        }

        $start = $working["start"];
        $end = $working["end"];
        $resolved = false;
        foreach ($existing as $avail) {
            if ($resolved)
                break;

            $id = $avail["internal_id"];
            $availStart = $avail["start"];
            $availEnd = $avail["end"];

            if (($availStart <= $start) && ($availEnd >= $end)) {
                // Skip creation, its a duplicate
                $resolved = true;
            }
            if (($availStart <= $start) && ($end > $availEnd) && ($start <= $availEnd)) {
                // Extend the end of the existing availability
                $resolved = true;
                $this->modifyAvailability($id, null, $end);
            }
            if (($start < $availStart) && ($availEnd >= $end) && ($end >= $availStart)) {
                // Extend the start of the existing availability (to earlier in the day)
                $resolved = true;
                $this->modifyAvailability($id, $start, null);
            }
            if (($start < $availStart) && ($end > $availEnd)) {
                // Extend the start and end of the existing availability
                $resolved = true;
                $this->modifyAvailability($id, $start, $end);
            }
        }

        // Some merge occured, delete the working availability
        if ($resolved && $performDelete) {
            $this->deleteEntry($working["internal_id"]);
        }

        // Merge occured, attempt again
        if ($resolved) {
            $this->cleanupAvailabiltiy($project_id, $dateStr, $provider, $location, $code);
            return true;
        }

        // Nothing was merged
        return false;
    }

    private function modifyAvailability($id_or_payload, $newStart = null, $newEnd = null)
    {
        $id = $id_or_payload;
        if (is_array($id_or_payload)) {
            $id = $id_or_payload["id"];
            $newStart = $id_or_payload["start"];
            $newEnd = $id_or_payload["end"];
        }
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
        $msg = "Availabiltiy $id updated to range $newStart to $newEnd";

        $this->log(
            "Modifed Availability",
            [
                "agent" => $this->getUser()->getUsername(),
                "start" => $newStart,
                "end" => $newEnd,
                "id" => $id
            ]
        );

        return [
            "msg" => $msg,
            "success" => true
        ];
    }

    private function deleteAvailability($payload)
    {
        if (isset($payload["start"]) && isset($payload["end"]) && isset($payload["id"])) {
            return $this->deleteSplitAvailability($payload);
        }
        if (isset($payload["start"]) && isset($payload["end"])) {
            return $this->deleteRangeAvailability($payload);
        }
        if (isset($payload["id"])) {
            // Can't log in delete entry as we won't even know if its Avail/Appt
            $result = $this->deleteEntry($payload);
            $this->log(
                "Deleted Availabiltiy Entry",
                [
                    "agent" => $this->getUser()->getUsername(),
                    ...$result["data"]
                ]
            );
            return $result;
        }
    }

    private function deleteSplitAvailability($payload)
    {
        $project_id = $payload["pid"];
        $start = $payload["start"];
        $end = $payload["end"];
        $id = $payload["id"];

        // Grab existing info
        $sql = $this->query("SELECT * FROM em_scheduling_calendar WHERE id = ?", [$id]);
        $row = db_fetch_assoc($sql);
        $provider = $row["user"];
        $location = $row["location"];
        $oldEnd = $row["time_end"];
        $code = $row["availability_code"];

        // Shrink existing availability, create new one
        $msg1 = $this->modifyAvailability($id, null, $start);
        $msg2 = $this->setAvailability([
            "pid" => $project_id,
            "group" => $code,
            "start" => $end,
            "end" => $oldEnd,
            "providers" => $provider,
            "locations" => $location
        ]);

        $this->log(
            "Split Availability",
            [
                "agent" => $this->getUser()->getUsername(),
                "split" => $start,
                "id" => $id
            ]
        );

        return [
            "msg" => "Availability split",
            "success" => true
        ];
    }

    private function deleteRangeAvailability($payload)
    {
        $codes = $payload["group"]; // Could be * for all
        $start = $payload["start"];
        $end = $payload["end"];
        $providers = $payload["providers"]; // Could be * for all
        $locations = $payload["locations"]; // Could be * for all

        $query = $this->createQuery();
        $query->add("DELETE FROM em_scheduling_calendar WHERE record IS NULL");

        if (!empty($codes) && $codes[0] != "*") {
            $query->add("AND")->addInClause("availability_code", $codes);
        }

        if (!empty($providers) && $providers[0] != "*") {
            $query->add("AND")->addInClause("user", $providers);
        }

        if (!empty($locations) && $locations[0] != "*") {
            $query->add("AND")->addInClause("location", $locations);
        }

        $query->add("AND time_start >= ? AND time_end <= ?", [$start, $end]);
        $query->execute();

        $this->log(
            "Deleted Availability Range",
            [
                "agent" => $this->getUser()->getUsername(),
                "start" => $start,
                "end" => $end,
                "providers" => $providers,
                "locations" => $locations,
                "codes" => $codes
            ]
        );

        return [
            "msg" => "Range delete ran with no issues",
            "success" => true
        ];
    }

    private function deleteEntry($id)
    {
        if (is_array($id)) {
            $id = $id["internal_id"] ?? $id["id"];
        }
        if (empty($id)) {
            return [
                "msg" => "No id provided",
                "success" => false
            ];
        }
        $result = $this->query("SELECT * FROm em_scheduling_calendar WHERE id = ?", [$id]);
        if ($result->num_rows == 0) {
            return [
                "msg" => "No entry found for id $id",
                "success" => false
            ];
        }
        $data = db_fetch_assoc($result);
        $this->query("DELETE FROM em_scheduling_calendar WHERE id = ?", [$id]);
        return [
            "msg" => "Entry $id was deleted",
            "success" => true,
            "data" => $data
        ];
    }

    private function getAppointments($payload)
    {
        $project_id = $payload["pid"];
        $allFlag = $payload["all_appointments"];
        $providers = $payload["providers"];
        $locations = $payload["locations"];
        $subjects = $payload["subjects"];
        $vists = $payload["visits"];
        $start = $payload["start"];
        $end = $payload["end"];

        $allUsers = $this->getAllUsers();
        $allLocations = $this->getLocationStructure(true); // we won't have all visits for My Cal Page
        $allVisits = $this->getVisits($payload); // we won't have all visits for My Cal Page
        $allSubjects = $allFlag ? $this->getGlobalSubjects($providers) : $this->getSubjects($payload);

        $query = $this->createQuery();
        $query->add("SELECT * FROM em_scheduling_calendar WHERE record IS NOT NULL");

        if (!$allFlag) {
            $query->add("AND project_id = ?", $project_id);
        }

        if (!empty($providers)) {
            $query->add("AND")->addInClause("user", $providers);
        }

        if (!empty($locations)) {
            $query->add("AND")->addInClause("location", $locations);
        }

        if (!empty($subjects)) {
            $query->add("AND")->addInClause("record", $subjects);
        }

        if (!empty($vists)) {
            $query->add("AND")->addInClause("visit", $locations);
        }

        $query->add("AND time_start >= ? AND time_end <= ?", [$start, $end]);

        $result = $query->execute();
        $appt = [];
        while ($row = $result->fetch_assoc()) {
            $allSubjectsRecord = $allFlag ? "$row[project_id]:$row[record]" : $row["record"];
            $appt[] = [
                "internal_id" => $row["id"],
                "project_id" => $row["project_id"],
                "title" => "Default Title",
                "start" => $row["time_start"],
                "end" => $row["time_end"],
                "location" => $row["location"],
                "location_display" => $allLocations[$row["location"]]["name"] ?? $row["location"],
                "user" => $row["user"],
                "user_display" => $allUsers[$row["user"]] ?? $row["user"],
                "visit" => $row["visit"],
                "visit_display" => $allVisits[$row["visit"]]["label"] ?? $row["visit"],
                "record" => $row["record"],
                "record_display" => $allSubjects[$allSubjectsRecord]["label"] ?? $row["record"],
                "notes" => $row["notes"],
                "metadata" => json_decode($row["metadata"], true) ?? [],
                "is_availability" => false,
                "is_appointment" => true
            ];
        }

        return $appt;
    }

    private function setAppointments($payload)
    {
        $project_id = $payload["pid"];
        $visit = $payload["visits"];
        $start = $payload["start"];
        $end = $payload["end"];
        $provider = $payload["providers"];
        $location = $payload["locations"];
        $record = $payload["subjects"];
        $notes = $payload["notes"];
        $notes = empty($notes) ? null : $notes; // If empty note then store null, not empty string

        // Check for duration
        $config = $this->getVisits($payload)[$visit];
        if (!empty($config["duration"])) {
            $duration = (strtotime($end) - strtotime($start)) / 60;
            $msg = "";
            if ($config["isExtendable"] && ($duration < $config["duration"]))
                $msg = "Appointment duration must be at least $config[duration] minutes";
            if (!$config["isExtendable"] && ($duration != $config["duration"]))
                $msg = "Appointment duration must be exactly $config[duration] minutes";
            if ($msg) {
                return [
                    "msg" => "Appointment duration must be exactly $config[duration] minutes",
                    "success" => false
                ];
            }
        }

        // Search for availability that overflows the start/end
        $payload["allow_overflow"] = true;
        $existing = $this->getAvailability($payload);

        if (count($existing) == 0) {
            return [
                "msg" => "Unable to add appontment, valid matching availability not found",
                "success" => false
            ];
        }
        $existing = $existing[0];
        $id = $existing["internal_id"];
        $exStart = $existing["start"];
        $exEnd = $existing["end"];

        if (($exStart == $start) && ($exEnd == $end)) {
            // Delete the availability, its a perfect overlap
            $this->deleteEntry($id);
        } elseif (($exStart == $start) || ($exEnd == $end)) {
            // Modify the availability
            $newStart = ($exStart == $start) ? $end : $exStart;
            $newEnd = ($exEnd == $end) ? $start : $exEnd;
            $this->modifyAvailability($id, $newStart, $newEnd);
        } else {
            // In the middle, modify and create new availability
            $this->modifyAvailability($id, $exStart, $start);
            $this->setAvailability([
                "pid" => $project_id,
                "start" => $end,
                "end" => $exEnd,
                "group" => $existing["availability_code"],
                "providers" => $existing["user"],
                "locations" => $existing["location"],
            ]);
        }

        // Create JSON with info for resotring avail if deleted
        $meta = json_encode([
            "restore" => [
                "pid" => $project_id,
                "group" => $existing["availability_code"],
                "providers" => $existing["user"],
                "locations" => $existing["location"],
            ]
        ]);

        // Writeback data to events
        $write = [];
        $vShared = $this->getVisits($payload, true);
        $vSet = $vShared["visits"][$visit];
        if (!empty($vShared["wbDateTimes"])) {
            $dd = Redcap::getDataDictionary($project_id, 'array', false, $vShared["wbDateTimes"]);
            foreach ($vShared["wbDateTimes"] as $dt) {
                $validation = $dd[$dt]["text_validation_type_or_show_slider_number"];
                list($date, $time) = explode(" ", $start);
                if (substr($validation, 0, 8) == "datetime")
                    $write[$dt] = $start;
                elseif (substr($validation, 0, 4) == "date")
                    $write[$dt] = $date;
                elseif (substr($validation, 0, 4) == "time") {
                    $hasSeconds = str_contains($validation, "hh_mm_ss");
                    $write[$dt] = $hasSeconds ? $time : substr($time, 0, 5);
                }
            }
        }
        if ($vShared["wbUser"])
            $write[$vShared["wbUser"]] = $provider;
        if (!empty($write) && $vSet["link"])
            REDCap::saveData($project_id, "array", [$record => [$vSet["link"] => $write]]);

        $this->query(
            "INSERT INTO em_scheduling_calendar (project_id, visit, user, record, location, time_start, time_end, notes, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$project_id, $visit, $provider, $record, $location, $start, $end, $notes, $meta]
        );

        $this->log(
            "Appointment Schedled",
            [
                "agent" => $this->getUser()->getUsername(),
                "provider" => $provider,
                "location" => $location,
                "start" => $start,
                "end" => $end,
                "record" => $record,
                "visit" => $visit,
                "notes" => $notes
            ]
        );

        return [
            "msg" => "Appointment scheduled",
            "success" => true,
        ];
    }

    private function modifyAppointments($payload)
    {
        $id = $payload["id"];
        $project_id = $payload["pid"];
        $provider = $payload["providers"];
        $location = $payload["locations"];

        // Grab needed info
        $sql = $this->query("SELECT visit, user, record, location FROM em_scheduling_calendar WHERE id = ? ", [$id]);
        $row = db_fetch_assoc($sql);
        $oldProvider = $row["user"];
        $oldLocation = $row["location"];
        $record = $row["record"];
        $visit = $row["visit"];

        // If provider is changed, restore old provider's Availability
        // and update the writeback if any is set
        if ($provider != $oldProvider) {
            $this->restoreAvailability($id);
            $vShared = $this->getVisits($payload, true);
            $vSet = $vShared["visits"][$visit];
            if ($vShared["wbUser"] && $vSet["link"])
                REDCap::saveData($project_id, "array", [$record => [$vSet["link"] => [$vShared["wbUser"] => $provider]]]);
        }

        // Do the update
        $this->query(
            "UPDATE em_scheduling_calendar SET user = ?, location = ?, metadata = NULL WHERE id = ?",
            [$provider, $location, $id]
        );

        $this->log(
            "Appointment Modifed",
            [
                "agent" => $this->getUser()->getUsername(),
                "provider" => $provider,
                "location" => $location,
                "id" => $id,
            ]
        );

        return [
            "msg" => "Appointment provider and/or location updated",
            "success" => true
        ];
    }

    private function deleteAppointments($payload)
    {
        $project_id = $payload["pid"];
        $id = $payload["id"];
        if (isset($payload["start"]) && isset($payload["end"])) {
            return $this->deleteRangeAppointments($payload);
        }
        if (isset($payload["id"])) {
            $this->restoreAvailability($id);

            // Blank out any write back
            $sql = $this->query("SELECT visit, record FROM em_scheduling_calendar WHERE id = ? ", [$id]);
            $row = db_fetch_assoc($sql);
            $visit = $row["visit"];
            $record = $row["record"];
            $vShared = $this->getVisits($payload, true);
            $vSet = $vShared["visits"][$visit];
            $write = [];
            if ($vShared["wbUser"])
                $write[$vShared["wbUser"]] = "";
            foreach ($vShared["wbDateTimes"] ?? [] as $dt)
                $write[$dt] = "";
            if (!empty($write) && $vSet["link"])
                REDCap::saveData($project_id, "array", [$record => [$vSet["link"] => $write]], "overwrite");

            $result = $this->deleteEntry($id);

            $this->log(
                "Deleted Appointment Entry",
                [
                    "agent" => $this->getUser()->getUsername(),
                    ...$result["data"]
                ]
            );

            return $result;
        }
    }

    private function restoreAvailability($id)
    {
        $meta = $this->getRowMetadata($id);
        if (empty($meta["restore"]))
            return;
        $this->setAvailability(
            array_merge(
                $meta["restore"],
                [
                    "start" => $meta["start"],
                    "end" => $meta["end"]
                ]
            )
        );
    }

    private function deleteRangeAppointments($payload)
    {
        $start = $payload["start"];
        $end = $payload["end"];
        $subjects = $payload["subjects"];
        $project_id = $payload["pid"];

        if (empty($subjects)) {
            return [
                "msg" => "Unable to delete appointment range, missing subject(s)",
                "success" => false
            ];
        }

        // Note: We don't touch writeback here. This func is used for in-the-past cleanup
        // and we don't want to junk the WB data.

        $query = $this->createQuery();
        $query->add("DELETE FROM em_scheduling_calendar WHERE record IS NOT NULL");
        $query->add("AND project_id = ?", [$project_id]);
        $query->add("AND")->addInClause("record", $subjects);
        $query->add("AND time_start >= ? AND time_end <= ?", [$start, $end]);
        $query->execute();

        $this->log(
            "Deleted Appointment Range",
            [
                "agent" => $this->getUser()->getUsername(),
                "start" => $start,
                "end" => $end,
                "subjects" => $subjects
            ]
        );

        return [
            "msg" => "Deleted range of appointments",
            "success" => true
        ];
    }

    private function getUserMetadata($payload)
    {
        $meta = $this->getProjectSetting("user-metadata");
        $meta = empty($meta) ? "{}" : $meta;
        return [
            "msg" => "User metadata retrieved",
            "success" => true,
            "data" => json_decode($meta)
        ];
    }

    private function setUserMetadata($payload)
    {
        $meta = $payload["metadata"];
        $this->setProjectSetting("user-metadata", json_encode($meta));
        return [
            "msg" => "User metadata updated",
            "success" => true
        ];
    }

    private function getRowMetadata($id)
    {
        $sql = $this->query("SELECT time_start, time_end, metadata from em_scheduling_calendar WHERE id = ?", [$id]);
        $row = db_fetch_assoc($sql);
        $meta = json_decode($row["metadata"] ?? "", true);
        $meta = $meta ?? [];
        $meta["start"] = $row["time_start"];
        $meta["end"] = $row["time_end"];
        return $meta;
    }

    public function makeICS($payload)
    {
        $project_id = is_array($payload) ? $payload["pid"] : $payload;
        $extraFields = $this->getProjectSetting("ics-field", $project_id) ?? [];

        $project_name = $this->getProjectName($project_id);
        $appts = $this->getAppointments([
            "pid" => $project_id,
            "start" => date('c', strtotime('-30 days')),
            "end" => date('c', strtotime('+60 days')),
            "providers" => [],
            "locations" => [],
            "subjects" => [],
            "visits" => [],
            "all_appointments" => false
        ]);
        $ics = "BEGIN:VCALENDAR
                VERSION:2.0
                PRODID:-//REDCap//NONSGML SchedulingCalendar EM//EN
                X-WR-CALNAME:REDCap Schedule Export - $project_id";

        $url = explode("ExternalModules", $this->getUrl("index.php"))[0] . "DataEntry/record_home.php";
        $data = $this->getSingleEventFields($extraFields, null, $project_id);
        $dd = REDCap::getDataDictionary($project_id, 'array', false, $extraFields);

        foreach ($appts as $a) {
            $desc = [
                $this->tt('ics_study') => $project_name,
                $this->tt('ics_provider') => $a['user_display'],
                $this->tt('ics_subject') => $a['record_display'],
                $this->tt('ics_visit') => $a['visit_display'],
            ];

            foreach ($extraFields as $field) {
                $desc[$dd[$field]["field_label"]] = $data[$a["record"]][$field];
            }

            $desc[$this->tt('ics_link')] = "$url?pid=$project_id&id={$a['record']}";

            $text = "";
            foreach ($desc as $title => $value) {
                $text = "{$text}{$title}: $value\\n";
            }

            $start = preg_replace("/[-:]/", "", str_replace(" ", "T", $a['start']));
            $end = preg_replace("/[-:]/", "", str_replace(" ", "T", $a['end']));
            $id = uniqid();

            $ics = "$ics
            BEGIN:VEVENT
            UID:$id
            DTSTAMP:$start
            ORGANIZER;CN=REDCap:MAILTO:{$this->getContactEmail()}
            DTSTART:$start
            DTEND:$end
            SUMMARY:$project_name-{$a['user_display']}
            DESCRIPTION:$text
            END:VEVENT";
        }

        return preg_replace("/ {4}/", "", "$ics\nEND:VCALENDAR");
    }

    private function fireDataEntryTrigger($payload)
    {
        // Chunks of this function are lifted from the DataEntry class
        global $data_entry_trigger_url, $data_entry_trigger_enabled;

        // Check if enabled
        if (!$data_entry_trigger_enabled || $data_entry_trigger_url == '') {
            return false;
        }

        // Build HTTP Post request parameters to send
        $params = [
            'redcap_url' => APP_PATH_WEBROOT_FULL,
            'project_url' => APP_PATH_WEBROOT_FULL . "redcap_v" . REDCAP_VERSION . "/index.php?pid=" . PROJECT_ID,
            'project_id' => PROJECT_ID,
            'username' => USERID
        ];

        // Add in stuff from save
        $params = array_merge($params, $payload);

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

    private function getSingleEventFields($fields, $records = null, $project_id = null)
    {
        $project_id = $project_id ?? $this->getProjectId();
        $fields = array_filter($fields);
        $data = REDCap::getData($project_id, 'array', $records, $fields);
        $results = [];
        foreach ($data as $record_id => $event_data) {
            foreach ($event_data as $event_id => $fields) {
                foreach ($fields as $field => $value) {
                    $results[$record_id][$field] = $this->escape($results[$record_id][$field] ?? $value);
                }
            }
        }
        return $results;
    }
}
