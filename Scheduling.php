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
        db_query("CREATE TABLE IF NOT EXISTS em_scheduling_calendar (
            `id` INT AUTO_INCREMENT,
            `project_id` INT,
            `visit` VARCHAR(255),
            `availability_code` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
            `user` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
            `record` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci,
            `location` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
            `time_start` timestamp NOT NULL,
            `time_end` timestamp NOT NULL,
            `notes` TEXT, 
            `metadata` JSON,
            PRIMARY KEY (`id`)
        );");
    }

    /*
    Load some config JS on the settings page
    */
    public function redcap_every_page_top($project_id)
    {
        if ($this->isPage("ExternalModules/manager/project.php") && $project_id)
            echo "<link rel='stylesheet' href='{$this->getUrl('style.css')}'>";
    }

    public function ics_cron($cronInfo)
    {
        // Stash original PID, probably not needed, but docs recommend
        $originalPid = $_GET['pid'];
        global $Proj;

        // Loop over every pid using this EM
        foreach ($this->getProjectsWithModuleEnabled() as $pid) {

            // Act like we are in that project
            $_GET['pid'] = $pid;
            $Proj = new Project($pid);

            // Gather a bunch of info
            $now = date("Y-m-d");
            $time = $this->getProjectSetting("ics-time", $pid);
            $extraFields = $this->getProjectSetting('ics-field', $pid);
            $freq = $this->getProjectSetting('ics-cron', $pid);
            $day = $this->getProjectSetting('ics-day', $pid);
            $users = $this->getProjectSetting('ics-user', $pid);

            if (empty($time))
                $this->setProjectSetting("ics-time", date("Y-m-d"));

            if (in_Array($freq, ["", null, "off"]) || (empty($day) && $freq == "week") || empty($users))
                return;

            if ((($freq == "day") && ($now > $time)) || (($freq == "week") && ($now > $time) && ($day == date("w")))) {
                $ics = $this->makeICS($extraFields, $pid);
                $this->sendICS($ics, $users, $pid);
                $this->setProjectSetting("ics-time", date("Y-m-d"));
            }
        }

        // Put the pid back the way it was before this cron job
        // likely doesn't matter, but is good housekeeping practice
        $_GET['pid'] = $originalPid;
        return "The \"{$cronInfo['cron_name']}\" cron job completed successfully.";
    }

    /*
    Process a post request from router
    */
    public function process()
    {
        $request = RestUtility::processRequest(false);
        $payload = $request->getRequestVars();
        $project_id = $payload["projectid"] ?? $_GET["pid"];
        $payload["pid"] = $project_id;
        $err_msg = "Not supported";
        $result = null;

        // Check if its the non-CRUD utility function
        if (!empty($payload["utility"]) && $payload["utility"] == "ics") {
            $result = [
                "data" => $this->utilityICS($payload),
                "success" => true
            ];
            return json_encode($result);
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
                "update" => "", # Use create for updates
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
            ]
        ][$payload["resource"]][$payload["crud"]];

        if ($payload["bundle"] && !empty($task)) {
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

    public function getProjectName($project_id = null)
    {
        $project_id = $project_id ?? $this->getProjectId();
        $sql = $this->query("SELECT app_title FROM redcap_projects WHERE project_id = ?", [$project_id]);
        return db_fetch_assoc($sql)["app_title"];
    }

    public function getContactEmail()
    {
        $sql = $this->query("SELECT value FROM redcap_config WHERE field_name = 'homepage_contact_email'", []);
        return db_fetch_assoc($sql)["value"];
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
                $name = $allUsers[$username] ?? "";
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
        // TODO: What issues might occur regarding dags and pulling subject names? 
        // Need to make sure we can't see those names
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

        $withdrawField = $this->getProjectSetting("withdraw-field", $project_id);
        $data = $this->getSingleEventFields([$nameField, $locationField, $withdrawField], null, $project_id);
        foreach ($data as $record_id => $recordData) {
            $name = $recordData[$nameField];
            $loc = $recordData[$locationField] ?? $locationStatic;
            $withdraw = boolval($recordData[$withdrawField]);
            $subjects[$record_id] = [
                "value" => $record_id,
                "label" => $name ?? $record_id,
                "location" => $loc,
                "name" => $name,
                "record_id" => $record_id,
                "is_withdrawn" => $withdraw,
                "project_id" => $project_id,
                "summary_fields" => [],
                "visits" => [
                    // "visit_code" = [
                    // "branching_logic" => true,
                    // "scheduled" => [],
                    // "range" => [] // TODO we don't even have config for this yet
                    // ];
                ]
            ];
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

        // Pull data needed for Branching Logic eval
        $visitSettings = $this->getVisits($payload);
        $blFields = $this->getProjectSetting("visit-branch-logic-field");
        $blData = $blFields ? REDCap::getData($project_id, "array", null, $blFields) : [];
        foreach ($visitSettings as $visit => $vSet) {
            $blValue = $vSet["blValue"];
            $blEvent = $vSet["blEvent"];
            $blField = $vSet["blField"];
            $subjects[$record]["visits"][$visit]["branching_logic"] = true;
            if ($blData && $blEvent && $blField) {
                $not = (count($blValue) > 0) && ($blValue[0] == "!");
                $v = $blData[$record][$blEvent][$blField];
                $subjects[$record]["visits"][$visit]["branching_logic"] = ($v == ($not ? substr($blValue, 1) : $blValue));
            }
        }

        // Check if any exta info is on the subject summary
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

    private function getGlobalSubjects($provider)
    {
        $sql = $this->query("SELECT * FROM em_scheduling_calendar WHERE user = '?' AND record IS NOT NULL", $provider);

        $data = [];
        while ($row = db_fetch_assoc($sql)) {
            $data[$row["project_id"]][$row["record"]] = $row["location"];
        }
        foreach ($data as $pid => $records) {
            $nameField = $this->getProjectSetting("name-field", $pid);
            $projectData = $this->getSingleEventFields([$nameField], $records, $pid);
            foreach ($projectData as $record_id => $record_data) {
                $loc = $records[$record_id];
                $name = $record_data[$nameField];
                $subjects["$pid:$record_id"] = [
                    "value" => $record_id,
                    "label" => $name ?? $record_id,
                    "location" => $loc,
                    "name" => $name,
                    "record_id" => $record_id,
                    "project_id" => $pid,
                    "is_withdrawn" => false
                ];
            }
        }
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
        $localCodes = array_map('trim', explode(',', $this->getProjectSetting("availability-codes")));
        $allFlag = $payload["all_availability"]; // Defaults to false when payload is null
        $allCodes = array_combine($this->getSystemSetting("group-code"), $this->getSystemSetting("group-name"));
        $result = [];
        foreach ($allCodes as $code => $name) {
            if ($allFlag || in_array($code, $localCodes)) {
                $result[$code] = [
                    "value" => $code,
                    "label" => $name,
                ];
            }
        }
        return $result;
    }

    private function getVisits($payload)
    {
        $project_id = $payload["pid"];
        $names = [
            "display-name" => "label",
            "code" => "code",
            "linked-event" => "link",
            "wb-datetime" => "wbDateTimes",
            "wb-user" => "wbUser",
            "notes" => "notes",
            "branch-logic-event" => "blEvent",
            "branch-logic-field" => "blField",
            "branch-logic-value" => "blValue",
            "duration" => "duration",
            "extendable" => "isExtendable",
            "location-free" => "isLocationFree", // TODO this isn't used right now
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

        $codes = array_map('trim', explode(',', $this->getProjectSetting("availability-codes")));
        if (empty($codes) && !$allFlag) {
            return $availability;
        }

        $allUsers = $this->getAllUsers();
        $allLocations = $this->getLocationStructure(true);

        $query = $this->createQuery();
        $query->add("SELECT * FROM em_scheduling_calendar WHERE record IS NULL");

        if (!$allFlag) {
            $query->add("AND")->addInClause("availability_code", $codes);
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

        $codes = $this->getAvailabilityCodes();
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

        if (empty($code) || empty($start) || empty($end) || empty($provider) || empty($location)) {
            return [
                "msg" => "Unable to set Availability, missing required info",
                "success" => false
            ];
        }

        $msg = "Modified existing availability";
        $mergeOccured = $this->cleanupAvailabiltiy($dateStr, $provider, $location, $code, null, [
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
        return [
            "msg" => $msg,
            "success" => true
        ];
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
            return true;
        }

        // Nothing was merged
        return false;
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
        $msg = "Availabiltiy $id updated to range $newStart to $newEnd";
        return [
            "msg" => $msg,
            "success" => true
        ];
    }

    private function deleteAvailability($payload)
    {
        if (isset($payload["start"]) && isset($payload["end"])) {
            return $this->deleteRangeAvailability($payload);
        }
        if (isset($payload["id"])) {
            return $this->deleteEntry($payload);
        }
    }

    private function deleteRangeAvailability($payload)
    {
        $codes = $payload["group"]; // Could be * for all
        $start = $payload["start"];
        $end = $payload["end"];
        $providers = $payload["providers"]; // Could be * for all
        $locations = $payload["locations"]; // Could be * for all

        if (empty($start) || empty($end)) {
            return [
                "msg" => "No start or end time provided",
                "success" => false
            ];
        }
        if (empty($providers)) {
            return [
                "msg" => "No providers provided",
                "success" => false
            ];
        }

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
                "success" => true
            ];
        }
        $this->query("DELETE FROM em_scheduling_calendar WHERE id = ?", [$id]);
        return [
            "msg" => "Entry $id was deleted",
            "success" => true
        ];
    }

    private function getAppointments($payload)
    {
        $appt = [];
        $project_id = $payload["pid"];
        $allFlag = $payload["all_appointments"];
        $providers = $payload["providers"];
        $locations = $payload["locations"];
        $subjects = $payload["subjects"];
        $vists = $payload["visits"];
        $start = $payload["start"];
        $end = $payload["end"];

        $allUsers = $this->getAllUsers();
        $allLocations = $this->getLocationStructure(true); // TODO we won't have all visits for My Cal Page
        $allVisits = $this->getVisits($payload); // TODO we won't have all visits for My Cal Page
        $allSubjects = null;
        if ($allFlag) {
            $allSubjects = $this->getGlobalSubjects($providers);
        } else {
            $allSubjects = $this->getSubjects($payload);
        }

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
        while ($row = $result->fetch_assoc()) {
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
                "record_display" => $allSubjects[$row["record"]]["label"] ?? $row["record"],
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

        if (empty($project_id) || empty($visit) || empty($start) || empty($end) || empty($provider) || empty($location) || empty($record)) {
            return [
                "msg" => "Unable to add Appointment, missing required info",
                "success" => false
            ];
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
            $this->modifyAvailabiltiy($id, $newStart, $newEnd);
        } else {
            // In the middle, modify and create new availability
            $this->modifyAvailabiltiy($id, $exStart, $start);
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
        $vSet = $this->getVisits($payload)[$visit];
        if (!empty($vSet["wbDateTimes"])) {
            $dd = Redcap::getDataDictionary($project_id, 'array', false, $vSet["wbDateTimes"]);
            foreach ($vSet["wbDateTimes"] as $dt) {
                $validation = $dd[$dt]["field_validation_type"];
                $hasSeconds = str_contains($validation, "_ss") || str_contains($validation, "_seconds");
                list($date, $time) = explode(" ", $start);
                $tmp = "";
                if (substr($validation, 0, 8) == "datetime")
                    $tmp = $start;
                elseif (substr($validation, 0, 4) == "time")
                    $tmp = $time;
                elseif (substr($validation, 0, 4) == "date")
                    $tmp = $date;
                $write[$dt] = $hasSeconds ? $tmp . ":00" : $tmp;
            }
        }
        if ($vSet["wbUser"])
            $write[$vSet["wbUser"]] = $provider;
        if (!empty($write) && $vSet["link"])
            REDCap::saveData($project_id, "array", [$record => [$vSet["link"] => $write]]);

        $this->query(
            "INSERT INTO em_scheduling_calendar (project_id, visit, user, record, location, time_start, time_end, notes, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$project_id, $visit, $provider, $record, $location, $start, $end, $notes, $meta]
        );

        return [
            "msg" => "Appointment scheduled",
            "success" => true
        ];
    }

    private function modifyAppointments($payload)
    {
        $id = $payload["id"];
        $project_id = $payload["pid"];
        $provider = $payload["providers"];
        $location = $payload["locations"];

        if (empty($id) || empty($provider) || empty($location)) {
            return [
                "msg" => "Unable to modify appontment, missing required fields",
                "success" => false
            ];
        }

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
            $vSet = $this->getVisits($payload)[$visit];
            if ($vSet["wbUser"] && $vSet["link"])
                REDCap::saveData($project_id, "array", [$record => [$vSet["link"] => [$vSet["wbUser"] => $provider]]]);
        }

        // Do the update
        $this->query(
            "UPDATE em_scheduling_calendar SET user = ?, location = ?, metadata = NULL WHERE id = ?",
            [$provider, $location, $id]
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
            $vSet = $this->getVisits($payload)[$visit];
            $write = [];
            if ($vSet["wbUser"])
                $write[$vSet["wbUser"]] = "";
            foreach ($vSet["wbDateTimes"] ?? [] as $dt)
                $write[$dt] = "";
            if (!empty($write) && $vSet["link"])
                REDCap::saveData($project_id, "array", [$record => [$vSet["link"] => $write]], "overwrite");

            return $this->deleteEntry($id);
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

        if (empty($subjects)) {
            return [
                "msg" => "Unable to delete appointment range, missing subject(s)",
                "success" => false
            ];
        }

        // TODO should we edit the writeback user and date/time to blank?

        $query = $this->createQuery();
        $query->add("DELETE FROM em_scheduling_calendar WHERE record IS NOT NULL");
        $query->add("AND")->addInClause("record", $subjects);
        $query->add("AND time_start >= ? AND time_end <= ?", [$start, $end]);
        $query->execute();

        return [
            "msg" => "Deleted range of appointments",
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

    private function utilityICS($payload)
    {
        $project_id = $payload["pid"];
        $extraFields = $this->getProjectSetting("ics-field") ?? [];
        return $this->makeICS($extraFields, $project_id);
    }

    private function makeICS($extaFields, $project_id)
    {
        $project_name = $this->getProjectName();
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
                PRODID:-//CTRI/REDCap Schedule//NONSGML v1.0//EN
                X-WR-CALNAME:REDCap Schedule Export - $project_id";

        $url = explode("ExternalModules", $this->getUrl("index.php"))[0] . "DataEntry/record_home.php";
        $data = $this->getSingleEventFields($extaFields, null, $project_id);
        $dd = REDCap::getDataDictionary($project_id, 'array', false, $extaFields);

        foreach ($appts as $a) {
            $desc = [
                $this->tt('ics_study') => $project_name,
                $this->tt('ics_provider') => $a['user_display'],
                $this->tt('ics_subject') => $a['record_display'],
                $this->tt('ics_visit') => $a['visit_display'],
            ];

            foreach ($extaFields as $field) {
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

    private function sendICS($ics, $users, $project_id)
    {
        $from = $this->getContactEmail();
        $project_name = $this->getProjectName($project_id);
        $subject = "[REDCap] ICS Backup - $project_id";
        $msg = "Attached is the ICS backup for project \"$project_name\" (PID $project_id)";
        $fileName = $project_name . $this->tt("ics_cal");
        $temp = tmpfile();
        fwrite($temp, $ics);
        $attachments = [
            $fileName => stream_get_meta_data($temp)['uri']
        ];

        foreach ($users as $user) {
            $userObj = $this->getUser($user);
            $to = $userObj->getEmail();
            if (empty($to))
                continue;
            REDCap::email($to, $from, $subject, $msg, null, null, null, $attachments);
        }

        fclose($temp);
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
        if ($project_id == null) {
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
