<?php

namespace UWMadison\Scheduling;

use ExternalModules\AbstractExternalModule;
use REDCap;
use Piping;
use DateTime;
use DateTimeZone;
use Throwable;

class Scheduling extends AbstractExternalModule
{
    private $schema = null; // API Schema
    private $visitCache = []; // Cache for visit data
    private $locationCache = []; // Cache for location data

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
    Check for action tag on data entry forms
    */
    public function redcap_data_entry_form($project_id, $record = null, $instrument = null, $event_id = null, $group_id = null, $repeat_instance = 1)
    {
        echo "<script>console.log('[SchedulingCalendar] redcap_data_entry_form hook fired:', " . json_encode([
            'project_id' => $project_id,
            'record' => $record,
            'instrument' => $instrument,
            'event_id' => $event_id
        ]) . ");</script>";
        $this->loadActionTag($project_id, $record, $instrument, $event_id);
    }

    /*
    Check for action tag on survey pages
    */
    public function redcap_survey_page($project_id, $record = null, $instrument = null, $event_id = null, $group_id = null, $survey_hash = null, $response_id = null, $repeat_instance = 1)
    {
        echo "<script>console.log('[SchedulingCalendar] redcap_survey_page hook fired:', " . json_encode([
            'project_id' => $project_id,
            'record' => $record,
            'instrument' => $instrument,
            'event_id' => $event_id
        ]) . ");</script>";
        $this->loadActionTag($project_id, $record, $instrument, $event_id);
    }

    /*
    Save pending appointment on form or survey submit
    */
    public function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id = null, $survey_hash = null, $response_id = null, $repeat_instance = 1)
    {
        if (empty($_POST['__scheduling_calendar_booking']) || !is_array($_POST['__scheduling_calendar_booking']))
            return;

        foreach ($_POST['__scheduling_calendar_booking'] as $fieldName => $bookingRaw) {
            $booking = is_array($bookingRaw) ? $bookingRaw : json_decode(stripslashes($bookingRaw), true);
            if (empty($booking) || empty($booking["visit"]) || empty($booking["start"]) || empty($booking["end"]))
                continue;

            $booking["visit"] = $this->resolveVisitCode($project_id, $booking["visit"]);

            // Check if there was an existing appointment on this record + visit to replace/reschedule
            $existingSql = $this->query(
                "SELECT id FROM em_scheduling_calendar WHERE project_id = ? AND record = ? AND visit = ? AND record IS NOT NULL",
                [$project_id, $record, $booking["visit"]]
            );
            while ($exRow = db_fetch_assoc($existingSql))
                $this->deleteAppointments(["pid" => $project_id, "id" => $exRow["id"]]);

            // Set appointment
            $payload = [
                "pid" => $project_id,
                "visits" => $booking["visit"],
                "start" => $booking["start"],
                "end" => $booking["end"],
                "providers" => $booking["provider"],
                "locations" => $booking["location"],
                "subjects" => $record,
                "notes" => "Scheduled via @SCHEDULING-CALENDAR on $instrument",
                "timezone" => "local"
            ];
            $this->setAppointments($payload);
        }
    }

    public function parseActionTag($annotation)
    {
        if (!preg_match('/@SCHEDULING-CALENDAR(?:\((.*?)\))?/is', $annotation, $matches))
            return null;

        $rawArgs = isset($matches[1]) ? trim($matches[1]) : "";
        $config = [
            "visit" => null,
            "start" => "now",
            "end" => "+30d",
            "duration" => null,
            "provider" => null,
            "location" => null,
            "mode" => "popup",
            "allow_reschedule" => false,
            "allow_cancel" => false,
            "hide_provider" => false,
            "save_mode" => "on_submit",
            "btn_text" => "Schedule Appointment",
            "timezone" => "browser"
        ];
        if (empty($rawArgs))
            return $config;

        if (str_contains($rawArgs, '=')) {
            preg_match_all('/([a-zA-Z_]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s,]+))/is', $rawArgs, $pairs, PREG_SET_ORDER);
            foreach ($pairs as $p) {
                $key = strtolower(trim($p[1]));
                $val = !empty($p[2]) ? $p[2] : (!empty($p[3]) ? $p[3] : ($p[4] ?? ""));
                if ($val === "true")
                    $val = true;
                elseif ($val === "false")
                    $val = false;
                elseif (is_numeric($val) && in_array($key, ["duration"]))
                    $val = (int)$val;
                $config[$key] = $val;
            }
        } else {
            $parts = str_getcsv($rawArgs, ",", "\"", "\\");
            $positionMap = [0 => "start", 1 => "end", 2 => "visit", 3 => "duration", 4 => "provider", 5 => "location", 6 => "mode"];
            foreach ($parts as $idx => $val) {
                $val = trim($val);
                if (isset($positionMap[$idx]) && $val !== "") {
                    $key = $positionMap[$idx];
                    if ($key === "duration" && is_numeric($val))
                        $val = (int)$val;
                    $config[$key] = $val;
                }
            }
        }
        if ($config["mode"] === "slot-list")
            $config["mode"] = "inline";
        return $config;
    }

    private function parseRelativeDate($raw, $server_tz, $isEnd = false)
    {
        $raw = trim($raw ?? "");
        $now = new DateTime("now", new DateTimeZone($server_tz));

        if (empty($raw))
            return $isEnd ? $now->format("Y-m-d 23:59:59") : $now->format("Y-m-d H:i:s");

        if (strtolower($raw) === "now")
            return $now->format("Y-m-d H:i:s");

        if (strtolower($raw) === "today")
            return $isEnd ? $now->format("Y-m-d 23:59:59") : $now->format("Y-m-d 00:00:00");

        if (preg_match('/^(?:(now|today)\s*)?([+-]?\s*\d+)\s*([a-zA-Z]+)$/i', $raw, $m)) {
            $base = strtolower($m[1] ?? "");
            $qty = (int)str_replace(' ', '', $m[2]);
            $unitRaw = strtolower($m[3]);

            $unit = null;
            $isTime = false;

            if (in_array($unitRaw, ['h', 'hr', 'hrs', 'hour', 'hours'])) {
                $unit = 'hours';
                $isTime = true;
            } elseif (in_array($unitRaw, ['min', 'mins', 'minute', 'minutes'])) {
                $unit = 'minutes';
                $isTime = true;
            } elseif (in_array($unitRaw, ['s', 'sec', 'secs', 'second', 'seconds'])) {
                $unit = 'seconds';
                $isTime = true;
            } elseif (in_array($unitRaw, ['d', 'day', 'days'])) {
                $unit = 'days';
                $isTime = ($base === 'now');
            } elseif (in_array($unitRaw, ['w', 'week', 'weeks'])) {
                $unit = 'weeks';
                $isTime = ($base === 'now');
            } elseif (in_array($unitRaw, ['mo', 'mon', 'month', 'months'])) {
                $unit = 'months';
                $isTime = ($base === 'now');
            } elseif (in_array($unitRaw, ['y', 'yr', 'year', 'years'])) {
                $unit = 'years';
                $isTime = ($base === 'now');
            } elseif ($unitRaw === 'm') {
                if ($base === 'now' || abs($qty) >= 15) {
                    $unit = 'minutes';
                    $isTime = true;
                } else {
                    $unit = 'months';
                    $isTime = false;
                }
            }

            if ($unit) {
                $dt = clone $now;
                if ($base === 'today')
                    $dt->setTime(0, 0, 0);
                $dt->modify("$qty $unit");
                if ($isTime)
                    return $dt->format("Y-m-d H:i:s");
                return $isEnd ? $dt->format("Y-m-d 23:59:59") : $dt->format("Y-m-d 00:00:00");
            }
        }

        $ts = strtotime($raw);
        if ($ts !== false) {
            if (strlen($raw) <= 10 && !str_contains($raw, ':'))
                return date($isEnd ? "Y-m-d 23:59:59" : "Y-m-d 00:00:00", $ts);
            return date("Y-m-d H:i:s", $ts);
        }

        return $isEnd ? $now->format("Y-m-d 23:59:59") : $now->format("Y-m-d 00:00:00");
    }

    public function resolveVisitCode($project_id, $visit)
    {
        if (empty($visit))
            return $visit;

        $visitConfigs = $this->getVisits(["pid" => $project_id]);
        if (isset($visitConfigs[$visit]))
            return $visit;

        foreach ($visitConfigs as $code => $cfg) {
            if ($code === $visit || (string)($cfg["link"] ?? "") === (string)$visit)
                return $code;

            if (!empty($cfg["link"]) && is_numeric($cfg["link"])) {
                $uniqueName = Piping::replaceVariablesInLabel("[event-name]", null, $cfg["link"], 1, null, false, $project_id, false);
                if ($uniqueName === $visit)
                    return $code;
            }
        }

        return $visit;
    }

    private function loadActionTag($project_id, $record, $instrument, $event_id)
    {
        echo "<script>console.log('[SchedulingCalendar] loadActionTag scanning instrument: " . json_encode($instrument) . " on project $project_id');</script>";

        $taggedFields = [];
        $fieldsScanned = 0;

        // Query redcap_metadata
        $metaSql = $this->createQuery();
        $metaSql->add("SELECT field_name, element_type, element_label, misc FROM redcap_metadata WHERE project_id = ?", [$project_id]);
        if (!empty($instrument))
            $metaSql->add("AND form_name = ?", [$instrument]);
        $metaResult = $metaSql->execute();
        while ($row = $metaResult->fetch_assoc()) {
            $fieldsScanned++;
            $annotation = $row['misc'] ?? '';
            if (str_contains($annotation, '@SCHEDULING-CALENDAR')) {
                $parsed = $this->parseActionTag($annotation);
                if ($parsed) {
                    $parsed['field_name'] = $row['field_name'];
                    $parsed['field_label'] = $row['element_label'];
                    if (!empty($parsed['visit'])) {
                        if (str_contains($parsed['visit'], '['))
                            $parsed['visit'] = Piping::replaceVariablesInLabel($parsed['visit'], $record, $event_id, 1, null, false, $project_id, false);
                        $parsed['visit'] = $this->resolveVisitCode($project_id, $parsed['visit']);
                    }
                    foreach (['provider', 'location', 'start', 'end'] as $param) {
                        if (!empty($parsed[$param]) && is_string($parsed[$param]) && preg_match('/^\[([a-zA-Z0-9_]+)\]$/', $parsed[$param], $m))
                            $parsed['piped_' . $param] = $m[1];
                    }
                    $taggedFields[$row['field_name']] = $parsed;
                }
            }
        }

        // If not found, also check redcap_metadata_temp (in case instrument changes are in draft mode)
        if (empty($taggedFields)) {
            $draftSql = $this->createQuery();
            $draftSql->add("SELECT field_name, element_type, element_label, misc FROM redcap_metadata_temp WHERE project_id = ?", [$project_id]);
            if (!empty($instrument))
                $draftSql->add("AND form_name = ?", [$instrument]);
            $draftResult = $draftSql->execute();
            while ($row = $draftResult->fetch_assoc()) {
                $fieldsScanned++;
                $annotation = $row['misc'] ?? '';
                if (str_contains($annotation, '@SCHEDULING-CALENDAR')) {
                    $parsed = $this->parseActionTag($annotation);
                    if ($parsed) {
                        $parsed['field_name'] = $row['field_name'];
                        $parsed['field_label'] = $row['element_label'];
                        if (!empty($parsed['visit'])) {
                            if (str_contains($parsed['visit'], '['))
                                $parsed['visit'] = Piping::replaceVariablesInLabel($parsed['visit'], $record, $event_id, 1, null, false, $project_id, false);
                            $parsed['visit'] = $this->resolveVisitCode($project_id, $parsed['visit']);
                        }
                        foreach (['provider', 'location', 'start', 'end'] as $param) {
                            if (!empty($parsed[$param]) && is_string($parsed[$param]) && preg_match('/^\[([a-zA-Z0-9_]+)\]$/', $parsed[$param], $m))
                                $parsed['piped_' . $param] = $m[1];
                        }
                        $taggedFields[$row['field_name']] = $parsed;
                    }
                }
            }
        }

        echo "<script>console.log('[SchedulingCalendar] Scanned $fieldsScanned fields on instrument \"$instrument\". Found " . count($taggedFields) . " tagged fields:', " . json_encode($taggedFields) . ");</script>";

        if (empty($taggedFields)) {
            echo "<script>console.warn('[SchedulingCalendar] No fields with @SCHEDULING-CALENDAR were found on this instrument ($instrument). If you just edited the field in Online Designer, make sure you clicked \"Save\" on the field.');</script>";
            return;
        }

        $this->initializeJavascriptModuleObject();
        $this->tt_transferToJavascriptModuleObject();
        $jsObj = $this->getJavascriptModuleObjectName();
        $actionTagConfig = [
            "projectId" => $project_id,
            "record" => $record,
            "eventId" => $event_id,
            "instrument" => $instrument,
            "fields" => $taggedFields,
            "timezones" => json_decode($this->getTimeZones($project_id), true)
        ];
        $configJson = json_encode($actionTagConfig);
        $scriptUrl = $this->getUrl('actionTag.js');
        $styleUrl = $this->getUrl('style.css');

        echo "<script>console.log('[SchedulingCalendar] Action tag active. Injecting bundle with config:', " . $configJson . ");</script>";
        echo "<link rel='stylesheet' href='{$styleUrl}'>";
        echo "<script>
            if (typeof {$jsObj} === 'undefined') window.{$jsObj} = {};
            {$jsObj}.actionTagConfig = {$configJson};
        </script>";
        echo "<script src='{$scriptUrl}' defer></script>";
    }

    /*
    Process AJAX request via External Module Framework native hook
    */
    public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id)
    {
        if ($action === 'calendar-api')
            return $this->process($payload, $project_id, $user_id);
        if ($action === 'survey-calendar-api')
            return $this->processSurveyApi($payload, $project_id, $record);
        http_response_code(400);
        return [
            "success" => false,
            "msg" => "Invalid action: $action"
        ];
    }

    public function processSurveyApi($payload, $project_id, $record)
    {
        if (empty($payload) || !is_array($payload)) {
            http_response_code(400);
            return [
                "success" => false,
                "msg" => "Payload is required and cannot be empty"
            ];
        }

        $action = $payload["action"];
        if (!empty($payload["visit"]))
            $payload["visit"] = $this->resolveVisitCode($project_id, $payload["visit"]);

        if ($action === "get-slots")
            return $this->getSurveySlots($payload, $project_id);

        if ($action === "get-appointment") {
            $visit = $payload["visit"];
            if (empty($record) || empty($visit)) {
                http_response_code(200);
                return [
                    "success" => true,
                    "has_appointment" => false
                ];
            }
            return $this->getSurveyAppointment($project_id, $record, $visit, $payload["timezone"] ?? "browser");
        }

        if ($action === "book-appointment") {
            $payload["pid"] = $project_id;
            $payload["subjects"] = $record;
            return $this->setAppointments($payload);
        }

        if ($action === "cancel-appointment") {
            $appointment_id = $payload["id"];
            if (empty($appointment_id)) {
                http_response_code(400);
                return [
                    "success" => false,
                    "msg" => "Appointment ID is required to cancel"
                ];
            }
            // Verify appointment belongs to this project and record
            $sql = $this->query("SELECT id FROM em_scheduling_calendar WHERE id = ? AND project_id = ? AND record = ?", [$appointment_id, $project_id, $record]);
            if (db_num_rows($sql) == 0) {
                http_response_code(403);
                return [
                    "success" => false,
                    "msg" => "Appointment not found or unauthorized to cancel"
                ];
            }
            return $this->deleteAppointments(["pid" => $project_id, "id" => $appointment_id]);
        }

        http_response_code(400);
        return [
            "success" => false,
            "msg" => "Unknown survey action: $action"
        ];
    }

    private function getSurveySlots($payload, $project_id)
    {
        $visit = $payload["visit"];
        if (empty($visit)) {
            http_response_code(400);
            return [
                "success" => false,
                "msg" => "Visit code is required"
            ];
        }

        $visitConfigs = $this->getVisits(["pid" => $project_id]);
        $visitConfig = $visitConfigs[$visit] ?? null;
        $duration = !empty($payload["duration"]) ? (int)$payload["duration"] : (!empty($visitConfig["duration"]) ? (int)$visitConfig["duration"] : 30);
        $duration = max(5, $duration);

        $timezone = $payload["timezone"] ?? "browser";
        $server_tz = date_default_timezone_get();
        $target_tz = ($timezone === "local" || $timezone === "browser" || empty($timezone)) ? $server_tz : $timezone;

        $rawStart = $payload["start"] ?? "now";
        $rawEnd = $payload["end"] ?? "+30d";
        $startDate = $this->parseRelativeDate($rawStart, $server_tz);
        $endDate = $this->parseRelativeDate($rawEnd, $server_tz, true);

        $providerFilter = !empty($payload["provider"]) ? array_map('trim', explode(',', $payload["provider"])) : [];
        $locationFilter = !empty($payload["location"]) ? array_map('trim', explode(',', $payload["location"])) : [];

        $unschedulables = (array)($this->getProjectSetting("unschedulable", $project_id) ?? []);

        $codes = $this->getAvailabilityCodes(["pid" => $project_id]);
        $codes_keys = array_keys($codes);
        if (empty($codes_keys)) {
            http_response_code(200);
            return ["success" => true, "slots" => [], "grouped" => []];
        }

        $query = $this->createQuery();
        $query->add("SELECT * FROM em_scheduling_calendar WHERE record IS NULL");
        $query->add("AND")->addInClause("availability_code", $codes_keys);
        $query->add("AND time_end > ? AND time_start < ?", [$startDate, $endDate]);
        if (!empty($providerFilter))
            $query->add("AND")->addInClause("user", $providerFilter);
        if (!empty($locationFilter))
            $query->add("AND")->addInClause("location", $locationFilter);
        $result = $query->execute();

        $availBlocks = [];
        while ($row = $result->fetch_assoc()) {
            if (in_array($row["user"], $unschedulables))
                continue;
            $availBlocks[] = $row;
        }

        if (empty($availBlocks)) {
            http_response_code(200);
            return ["success" => true, "slots" => [], "grouped" => []];
        }

        $apptQuery = $this->createQuery();
        $apptQuery->add("SELECT user, location, time_start, time_end FROM em_scheduling_calendar WHERE record IS NOT NULL");
        $apptQuery->add("AND project_id = ?", [$project_id]);
        $apptQuery->add("AND time_end > ? AND time_start < ?", [$startDate, $endDate]);
        $apptResult = $apptQuery->execute();
        $bookedAppts = [];
        while ($row = $apptResult->fetch_assoc())
            $bookedAppts[] = $row;

        $allUsers = $this->getAllUsers();
        $allLocations = $this->getLocationStructure($project_id, true);

        $now = date('Y-m-d H:i:s');
        $minStart = max(strtotime($startDate), strtotime($now));
        $maxEnd = strtotime($endDate);
        $slots = [];
        $slotStepSec = $duration * 60;

        foreach ($availBlocks as $block) {
            $bStart = strtotime($block["time_start"]);
            $bEnd = min(strtotime($block["time_end"]), $maxEnd);
            $provider = $block["user"];
            $loc = $block["location"];

            for ($slotStart = $bStart; $slotStart + $slotStepSec <= $bEnd; $slotStart += $slotStepSec) {
                if ($slotStart < $minStart)
                    continue;
                $slotEnd = $slotStart + $slotStepSec;
                $slotStartStr = date('Y-m-d H:i:s', $slotStart);
                $slotEndStr = date('Y-m-d H:i:s', $slotEnd);

                $overlap = false;
                foreach ($bookedAppts as $b) {
                    if ($b["user"] === $provider) {
                        $bStart = strtotime($b["time_start"]);
                        $bEnd = strtotime($b["time_end"]);
                        if ($slotStart < $bEnd && $slotEnd > $bStart) {
                            $overlap = true;
                            break;
                        }
                    }
                }
                if ($overlap)
                    continue;

                $dtStart = new DateTime($slotStartStr, new DateTimeZone($server_tz));
                $dtStart->setTimezone(new DateTimeZone($target_tz));
                $dtEnd = new DateTime($slotEndStr, new DateTimeZone($server_tz));
                $dtEnd->setTimezone(new DateTimeZone($target_tz));

                $slots[] = [
                    "start" => $slotStartStr,
                    "end" => $slotEndStr,
                    "provider" => $provider,
                    "provider_name" => $allUsers[$provider] ?? $provider,
                    "location" => $loc,
                    "location_name" => $allLocations[$loc]["name"] ?? $loc,
                    "date_key" => $dtStart->format('Y-m-d'),
                    "date_display" => $dtStart->format('l, F j, Y'),
                    "time_display" => $dtStart->format('g:i A') . ' - ' . $dtEnd->format('g:i A'),
                    "start_display" => $dtStart->format('g:i A'),
                    "end_display" => $dtEnd->format('g:i A')
                ];
            }
        }

        usort($slots, function ($a, $b) {
            return strcmp($a["start"], $b["start"]);
        });

        $grouped = [];
        foreach ($slots as $s)
            $grouped[$s["date_key"]][] = $s;

        http_response_code(200);
        return [
            "success" => true,
            "duration" => $duration,
            "timezone" => $target_tz,
            "slots" => $slots,
            "grouped" => $grouped
        ];
    }

    private function getSurveyAppointment($project_id, $record, $visit, $timezone = "browser")
    {
        $server_tz = date_default_timezone_get();
        $target_tz = ($timezone === "local" || $timezone === "browser" || empty($timezone)) ? $server_tz : $timezone;

        $sql = $this->query(
            "SELECT * FROM em_scheduling_calendar WHERE project_id = ? AND record = ? AND visit = ? AND record IS NOT NULL ORDER BY time_start DESC LIMIT 1",
            [$project_id, $record, $visit]
        );
        if ($row = db_fetch_assoc($sql)) {
            $allUsers = $this->getAllUsers();
            $allLocations = $this->getLocationStructure($project_id, true);

            $dtStart = new DateTime($row["time_start"], new DateTimeZone($server_tz));
            $dtStart->setTimezone(new DateTimeZone($target_tz));
            $dtEnd = new DateTime($row["time_end"], new DateTimeZone($server_tz));
            $dtEnd->setTimezone(new DateTimeZone($target_tz));

            http_response_code(200);
            return [
                "success" => true,
                "has_appointment" => true,
                "appointment" => [
                    "id" => (int)$row["id"],
                    "start" => $row["time_start"],
                    "end" => $row["time_end"],
                    "provider" => $row["user"],
                    "provider_name" => $allUsers[$row["user"]] ?? $row["user"],
                    "location" => $row["location"],
                    "location_name" => $allLocations[$row["location"]]["name"] ?? $row["location"],
                    "date_display" => $dtStart->format('l, F j, Y'),
                    "time_display" => $dtStart->format('g:i A') . ' - ' . $dtEnd->format('g:i A')
                ]
            ];
        }
        http_response_code(200);
        return [
            "success" => true,
            "has_appointment" => false
        ];
    }

    public function getSafeUser($username = null)
    {
        try {
            return $this->getUser($username);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function isCalendarAdmin($project_id = null, $username = null)
    {
        $user = $this->getSafeUser($username);
        if ($user && $user->isSuperUser())
            return true;
        $username = $user ? $user->getUsername() : $username;
        if (empty($username))
            return false;
        $admins = $this->getProjectSetting("calendar-admin", $project_id) ?? [];
        return in_array($username, (array)$admins);
    }

    public function canManageAvailability($targetProvider, $project_id = null, $actingUsername = null)
    {
        $actingUsername = $actingUsername ?? $this->getSafeUser()?->getUsername();
        if (empty($actingUsername))
            return false;
        // Providers can always manage their own availability
        if ($targetProvider === $actingUsername)
            return true;
        // Otherwise must be a calendar admin
        return $this->isCalendarAdmin($project_id, $actingUsername);
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
    Process a request from ajax hook
    */
    public function process($payload, $context_project_id = null, $context_user_id = null)
    {
        if (empty($payload) || !is_array($payload)) {
            http_response_code(400);
            return [
                "success" => false,
                "msg" => "Payload is required and cannot be empty"
            ];
        }

        $project_id = $context_project_id ?? $this->getProjectId();
        if (empty($project_id)) {
            http_response_code(400);
            return [
                "success" => false,
                "msg" => "Project ID is required",
                "payload" => $payload
            ];
        }
        $payload["pid"] = $project_id;

        // Check if its the non-CRUD utility function
        if (!empty($payload["utility"]) && $payload["utility"] === "ics") {
            http_response_code(200);
            return [
                "data" => $this->makeICS($payload),
                "success" => true
            ];
        }

        if (empty($payload["resource"]) || empty($payload["crud"])) {
            http_response_code(400);
            return [
                "success" => false,
                "msg" => "Payload must specify 'resource' and 'crud'",
                "payload" => $payload
            ];
        }

        $resource = $payload["resource"];
        $crud = $payload["crud"];

        // CRUD task mapping
        $taskMap = [
            "availabilitycode" => [
                "read" => "getAvailabilityCodes",
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
            ],
            "subject" => [
                "read" => "getSubjects",
            ],
            "location" => [
                "read" => "getLocations",
            ],
            "visit" => [
                "read" => "getVisits",
            ],
            "metadata" => [
                "read" => "getUserMetadata",
                "update" => "setUserMetadata",
            ],
        ];

        if (!isset($taskMap[$resource][$crud])) {
            http_response_code(400);
            return [
                "success" => false,
                "msg" => "Unsupported operation: resource '{$resource}', action '{$crud}'",
                "payload" => $payload
            ];
        }
        $task = $taskMap[$resource][$crud];

        // Validate against API schema
        $schema = $this->getSchema()[$resource][$crud] ?? null;
        if (!empty($schema)) {
            $schemaValid = false;
            $innerPayload = (isset($payload["bundle"]) && is_array($payload["bundle"]) && !empty($payload["bundle"]))
                ? $payload["bundle"][0]
                : $payload;
            foreach ($schema as $schemaOption) {
                if (count(array_intersect_key(array_flip($schemaOption), $innerPayload)) === count($schemaOption)) {
                    $schemaValid = true;
                    break;
                }
            }
            if (!$schemaValid) {
                http_response_code(400);
                return [
                    "success" => false,
                    "msg" => "Missing required parameters for '{$resource}' '{$crud}'",
                    "payload" => $payload
                ];
            }
        }

        $err_msg = "";
        $result = null;

        if (!empty($payload["bundle"]) && is_array($payload["bundle"])) {
            $result = [];
            foreach ($payload["bundle"] as $subPayload) {
                $subPayload["pid"] = $project_id;
                $res = $this->$task($subPayload);
                if (is_array($res) && isset($res["success"]) && !$res["success"])
                    $err_msg = $res["msg"] ?? "Operation failed";
                $result[] = $res;
            }
        } else {
            $result = $this->$task($payload);
            if (is_array($result) && isset($result["success"]) && !$result["success"])
                $err_msg = $result["msg"] ?? "Operation failed";
        }

        // Fire DET at the end only if operation succeeded
        if (
            empty($err_msg) &&
            $this->getProjectSetting('fire-det', $project_id) &&
            in_array($crud, ["create", "update", "delete"]) &&
            in_array($resource, ["availability", "appointment"])
        ) {
            $detPayload = is_array($result) ? array_merge($result, $payload) : $payload;
            $this->fireDataEntryTrigger($detPayload);
        }

        // Return the error or result
        if (!empty($err_msg)) {
            http_response_code(400);
            return [
                "success" => false,
                "msg" => $err_msg,
                "payload" => $payload
            ];
        }

        http_response_code(200);
        return $result;
    }

    /*
    Get info on the current user
    */
    public function currentUser()
    {
        $user = $this->getSafeUser();
        $username = $user?->getUsername();
        if (empty($username))
            return [
                "username" => "",
                "email" => "",
                "name" => "",
                "isCalendarAdmin" => false,
                "isSuperUser" => false,
                "icsHash" => ""
            ];
        $isAdmin = $this->isCalendarAdmin(null, $username);
        $hash = "";
        if ($isAdmin) {
            $json = json_decode($this->getProjectSetting("ics-hash-json") ?? "{}", true);
            if (!is_array($json))
                $json = [];
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
            "email" => $user ? $user->getEmail() : "",
            "name" => trim(($GLOBALS['user_firstname'] ?? '') . ' ' . ($GLOBALS['user_lastname'] ?? '')) ?: $username,
            "isCalendarAdmin" => $isAdmin,
            "isSuperUser" => $user ? $user->isSuperUser() : false,
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

    public function getTimeZones($project_id = null)
    {
        $project_id = $project_id ?? $this->getProjectId();
        $local = date_default_timezone_get();
        $config = $project_id ? $this->getProjectSetting("timezones", $project_id) : $this->getProjectSetting("timezones");
        $timezones = [];

        if ($config) {
            $lines = array_map('trim', explode("\n", $config));
            $validTimezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);

            foreach ($lines as $line) {
                if (empty($line)) continue;

                // Parse "timezone, alias" format
                $parts = array_map('trim', explode(',', $line, 2));
                $timezone = $parts[0];
                $alias = isset($parts[1]) ? $parts[1] : $timezone;

                // Only include valid timezones
                if (in_array($timezone, $validTimezones)) {
                    $timezones[] = [
                        'value' => $timezone,
                        'label' => $alias
                    ];
                }
            }
        }

        // Add local timezone if not already present
        $localExists = false;
        foreach ($timezones as $tz) {
            if ($tz['value'] !== $local)
                continue;
            $localExists = true;
            break;
        }

        if (!$localExists) {
            array_unshift($timezones, [
                'value' => $local,
                'label' => $local
            ]);
        } else {
            // Move local timezone to the front
            $localTimezone = null;
            $timezones = array_filter($timezones, function ($tz) use ($local, &$localTimezone) {
                if ($tz['value'] !== $local)
                    return true;
                $localTimezone = $tz;
                return false;
            });
            array_unshift($timezones, $localTimezone);
        }

        return json_encode($timezones);
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
        while ($row = db_fetch_assoc($sql))
            $globalProviders[] = $row["user"];

        // Get all local users & Settings
        $localProviders = (array)(REDCap::getUsers() ?? []);
        $unschedulables = (array)($this->getProjectSetting("unschedulable") ?? []);
        $admins = (array)($this->getProjectSetting("calendar-admin") ?? []);

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
        while ($row = db_fetch_assoc($sql))
            $users[$row["username"]] = $row["displayname"];
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
            $name = htmlspecialchars_decode(htmlspecialchars_decode($recordData[$nameField] ?? '')); # We are forced to double encode by Vanderbilt
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
                    $not = (strlen($blValue) > 0) && ($blValue[0] == "!");
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
        $timezone = $payload["timezone"] ?? "local";
        $server_tz = date_default_timezone_get();
        $timezone = $timezone == $server_tz ? "local" : $timezone;

        $query = $this->createQuery();
        $query->add("SELECT record, visit, time_start from em_scheduling_calendar WHERE project_id = ?", $project_id);
        $query->add("AND")->addInClause("record", array_keys($subjects));
        $result = $query->execute();
        while ($row = $result->fetch_assoc()) {
            $record = $row["record"];
            $visit = $row["visit"];
            $start = $row["time_start"];
            if ($timezone != "local") {
                $dtStart = new DateTime($start, new DateTimeZone($server_tz));
                $dtStart->setTimezone(new DateTimeZone($timezone));
                $start = $dtStart->format('Y-m-d H:i:s');
            }
            $subjects[$record]["visits"][$visit]["scheduled"][] = $start;
        }

        // Check if any exta info is on the subject summary (3rd query)
        $extraFields = $this->getProjectSetting("ss-field");
        if (!empty($extraFields)) {
            $dd = Redcap::getDataDictionary($project_id, 'array', false, $extraFields);
            $eData = $this->getSingleEventFields($extraFields, null, $project_id);
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
        // Pull all info for the given provider
        // Currently we only ever have one provider
        if (!is_array($providers))
            $providers = [$providers];
        if (count($providers) == 0)
            return [];
        $query = $this->createQuery();
        $query->add("SELECT * FROM em_scheduling_calendar WHERE record IS NOT NULL");
        $query->add("AND")->addInClause("user", $providers);
        $sql = $query->execute();

        $data = [];
        $subjects = [];
        while ($row = db_fetch_assoc($sql))
            $data[$row["project_id"]][$row["record"]] = $row["location"];
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
        return $this->getLocationStructure($payload["pid"]);
    }

    private function getLocationStructure($project_id, $flatten = false)
    {
        $cacheName = $project_id . ($flatten ? "-flat" : "");
        if ($this->locationCache[$cacheName])
            return $this->locationCache[$cacheName];

        $locations = [];
        // Find where to pull info from
        $sot = $this->getProjectSetting("location-sot", $project_id);
        if (in_array($sot, ["json", "pid"])) {
            $project_id = $sot == "json" ? $project_id : $this->getProjectSetting("location-pid", $project_id);
            // Decode the JSON
            $locations = $this->getProjectSetting("location-json", $project_id);
            $locations = json_decode($locations, true) ?? [];
        } elseif ($sot == "field") {
            // Pull from field options
            $field = $this->getProjectSetting("location-field-options", $project_id);
            $dd = REDCap::getDataDictionary($project_id, 'array', false, [$field]);
            $pairs = explode("|", $dd[$field]["select_choices_or_calculations"]);
            foreach ($pairs as $pair) {
                $parts = explode(",", $pair, 2);
                $code = trim($parts[0]);
                $name = trim($parts[1]);
                $locations[$code] = [
                    "name" => $name,
                    "active" => true,
                ];
            }
        }

        // Done?
        if (!$flatten) {
            $this->locationCache[$cacheName] = $locations;
            return $locations;
        }

        // Flatten the locations
        $flat_locations = [];
        foreach ($locations as $code => $data) {
            $sites = $data["sub"];
            unset($data["sub"]);
            $flat_locations[$code] = $data;
            foreach ($sites as $site_code => $site) {
                $flat_locations[$site_code] = array_merge($site, ["parent" => $code]);
            }
        }

        $this->locationCache[$cacheName] = $flat_locations;
        return $flat_locations;
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
        $allCodes = array_combine(
            $this->getSystemSetting("group-code") ?? [],
            $this->getSystemSetting("group-name") ?? []
        );
        $result = [];
        if ($globalFlag)
            $result["global"] = [
                "value" => "global",
                "label" => "Global",
                "isLocal" => false
            ];
        if ($localFlag)
            $result[$project_id] = [
                "value" => $project_id,
                "label" => "This Project",
                "isLocal" => true
            ];
        foreach ($allCodes as $code => $name) {
            $isLocal = in_array($code, $localCodes);
            if ($allFlag || $isLocal)
                $result[$code] = [
                    "value" => $code,
                    "label" => $name,
                    "isLocal" => $isLocal
                ];
        }
        return $result;
    }

    private function getVisits($payload, $includeSharedConfig = false)
    {
        $project_id = $payload["pid"];
        $cacheName = $project_id . ($includeSharedConfig ? "-shared" : "");
        if ($this->visitCache[$cacheName])
            return $this->visitCache[$cacheName];
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

        $numVisits = !empty($values[0]) && is_array($values[0]) ? count($values[0]) : 0;
        $visits = [];
        if ($numVisits > 0) {
            for ($i = 0; $i < $numVisits; $i++) {
                $tmp = [];
                $k = 0;
                foreach ($names as $setting => $alias) {
                    $tmp[$alias] = $values[$k][$i] ?? null;
                    $k++;
                }
                $tmp["value"] = $tmp["code"] ?? "";
                if (!empty($tmp["code"]))
                    $visits[$tmp["code"]] = $tmp;
            }
        }

        if ($includeSharedConfig)
            $visits = [
                "visits" => $visits,
                "wbDateTimes" => $this->getProjectSetting("wb-datetime", $project_id),
                "wbUser" => $this->getProjectSetting("wb-user", $project_id),
                "rangeStart" => $this->getProjectSetting("range-start", $project_id),
                "rangeEnd" => $this->getProjectSetting("range-end", $project_id),
            ];

        $this->visitCache[$cacheName] = $visits;
        return $visits;
    }

    private function getAvailability($payload)
    {
        $availability = [];
        $project_id = $payload["pid"];
        $providers = $payload["providers"];
        $locations = $payload["locations"];
        $start = $payload["start"];
        $end = $payload["end"];
        $allFlag = $payload["all_availability"];
        $overflowFlag = $payload["allow_overflow"]; // Internal param for scheduling
        $timezone = $payload["timezone"];
        $server_tz = date_default_timezone_get();
        $timezone = $timezone == $server_tz ? "local" : $timezone;

        $codes = $this->getAvailabilityCodes($payload);
        $codes_keys = array_keys($codes);
        if (empty($codes_keys) && !$allFlag)
            return $availability;

        $allUsers = $this->getAllUsers();
        $allLocations = $this->getLocationStructure($project_id, true);

        $query = $this->createQuery();
        $query->add("SELECT * FROM em_scheduling_calendar WHERE record IS NULL");

        if (!$allFlag)
            $query->add("AND")->addInClause("availability_code", $codes_keys);

        if (!empty($providers))
            $query->add("AND")->addInClause("user", $providers);

        if (!empty($locations))
            $query->add("AND")->addInClause("location", $locations);

        if ($timezone != "local") {
            $dtStart = new DateTime($start, new DateTimeZone($timezone));
            $dtStart->setTimezone(new DateTimeZone($server_tz));
            $start = $dtStart->format('Y-m-d H:i:s');
            $dtEnd = new DateTime($end, new DateTimeZone($timezone));
            $dtEnd->setTimezone(new DateTimeZone($server_tz));
            $end = $dtEnd->format('Y-m-d H:i:s');
        }

        if (!$overflowFlag) {
            $query->add("AND time_start >= ? AND time_end <= ?", [$start, $end]);
        } else {
            $query->add("AND time_start <= ? AND time_end >= ?", [$start, $end]);
            // TODO If availability was scheduled in two seperate projects (or more) that works
            // then they won't be merged into one availability block. 
            // This is a known limitation of the current design. That availabilty will be overlooked
            // and an "unable to schedule" message will be shown to the user. This is a rare case and can be fixed in a future version.
        }

        $result = $query->execute();
        while ($row = $result->fetch_assoc()) {
            $provider = $allUsers[$row["user"]] ?? $row["user"];
            $location = $allLocations[$row["location"]]["name"] ?? $row["location"];
            $codeName = $codes[$row["availability_code"]]["label"] ?? $row["availability_code"];
            $start = $row["time_start"];
            $end = $row["time_end"];
            if ($timezone != "local") {
                $dtStart = new DateTime($start, new DateTimeZone($server_tz));
                $dtStart->setTimezone(new DateTimeZone($timezone));
                $start = $dtStart->format('Y-m-d H:i:s');
                $dtEnd = new DateTime($end, new DateTimeZone($server_tz));
                $dtEnd->setTimezone(new DateTimeZone($timezone));
                $end = $dtEnd->format('Y-m-d H:i:s');
            }
            $availability[] = [
                "internal_id" => $row["id"],
                "project_id" => $row["project_id"],
                "title" => "Default Title",
                "start" => $start,
                "end" => $end,
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

    private function setAvailability($payload, $restoreBypass = false)
    {
        $project_id = $payload["pid"];
        $code = $payload["group"];
        $start = $payload["start"];
        $end = $payload["end"];
        $provider = $payload["providers"];
        $location = $payload["locations"];
        if (!$restoreBypass && !$this->canManageAvailability($provider, $project_id))
            return [
                "msg" => "Permission denied: Only calendar administrators or the provider can set this availability",
                "success" => false
            ];
        $timezone = $payload["timezone"] ?? "local";
        $server_tz = date_default_timezone_get();
        $timezone = $timezone == $server_tz ? "local" : $timezone;

        $sql = "INSERT INTO em_scheduling_calendar (project_id, availability_code, user, location, time_start, time_end) VALUES (?, ?, ?, ?, ?, ?)";
        $logData = [
            "agent" => $this->getSafeUser()?->getUsername(),
            "provider" => $provider,
            "location" => $location,
            "start" => $start,
            "end" => $end,
            "code" => $code,
            "timezone" => $timezone
        ];

        if ($timezone != "local") {
            $dtStart = new DateTime($start, new DateTimeZone($timezone));
            $dtStart->setTimezone(new DateTimeZone($server_tz));
            $start = $dtStart->format('Y-m-d H:i:s');
            $dtEnd = new DateTime($end, new DateTimeZone($timezone));
            $dtEnd->setTimezone(new DateTimeZone($server_tz));
            $end = $dtEnd->format('Y-m-d H:i:s');
            $payload["start"] = $start;
            $payload["end"] = $end;
            $payload["timezone"] = "local";
        }

        $dateStr = substr($start, 0, 10);

        if ($restoreBypass) {
            $msg = "Availability Restored";
            $this->log($msg, $logData);
            $this->query($sql, [$project_id, $code, $provider, $location, $start, $end]);
            $this->cleanupAvailability($project_id, $dateStr, $provider, $location, $code, null, [
                "start" => $start,
                "end" => $end
            ]);
            return [
                "msg" => $msg,
                "success" => true
            ];
        }

        // Stop any multi-day availability
        $dateStr2 = substr($end, 0, 10);
        if ($dateStr != $dateStr2) {
            $this->log(
                "Bug: Attempting to add multi-day availability in one action.",
                $logData
            );
            return [
                "msg" => "Start and End must be on the same day",
                "success" => false
            ];
        }

        // Search for existing appts, don't allow overlap with this new availability
        $start_of_day = $dateStr . " 00:00";
        $end_of_day = $dateStr . " 23:59";
        $appts = $this->getAppointments([
            ...$payload,
            "start" => $start_of_day,
            "end" => $end_of_day,
        ]);
        foreach ($appts as $appt) {
            $apptStart = $appt["start"];
            $apptEnd = $appt["end"];
            if (($apptEnd <= $start) || ($apptStart >= $end))
                continue; // No overlap at all
            if (($apptStart <= $start) && ($apptEnd >= $end))
                return [
                    "msg" => "Availability overlaps with existing appointment",
                    "success" => false
                ];
            if (($apptStart <= $start) && ($apptEnd < $end) && ($start < $apptEnd)) {
                $this->log(
                    "Requested availability overlaps with existing appointment, modifying request.",
                    $logData
                );
                return $this->setAvailability([
                    ...$payload,
                    "start" => $apptEnd
                ]);
            }
            if (($apptStart > $start) && ($apptEnd >= $end) && ($end > $apptStart)) {
                $this->log(
                    "Requested availability overlaps with existing appointment, modifying request.",
                    $logData
                );
                return $this->setAvailability([
                    ...$payload,
                    "end" => $apptStart
                ]);
            }
            if (($apptStart > $start) && ($apptEnd < $end)) {
                $this->log(
                    "Requested availability overlaps with existing appointment, modifying request.",
                    $logData
                );
                $this->setAvailability([
                    ...$payload,
                    "end" => $apptStart
                ]);
                return $this->setAvailability([
                    ...$payload,
                    "start" => $apptEnd
                ]);
            }
        }

        // Cleanup any existing availability that overlaps with out availability
        $msg = "Modified existing availability";
        $mergeOccured = $this->cleanupAvailability($project_id, $dateStr, $provider, $location, $code, null, [
            "start" => $start,
            "end" => $end
        ]);

        // No cleanup occured, insert new availability
        if (!$mergeOccured) {
            $msg = "Inserted new availability";
            $this->query(
                $sql,
                [$project_id, $code, $provider, $location, $start, $end]
            );
        }

        // Log the action
        $this->log(
            "Availability Added" . ($mergeOccured ? " (merged with existing availability)" : ""),
            $logData
        );

        return [
            "msg" => $msg,
            "success" => true
        ];
    }

    private function cleanupAvailability($project_id, $dateStr, $provider, $location, $code, $existing = null, $working = null)
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
                "end" => $end_of_day,
                "timezone" => "local",
                "all_availability" => true
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

            // Skip creation, its a duplicate
            if (($availStart <= $start) && ($availEnd >= $end))
                $resolved = true;
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
        if ($resolved && $performDelete)
            $this->deleteEntry($working["internal_id"]);

        // Merge occured, attempt again
        if ($resolved) {
            $this->cleanupAvailability($project_id, $dateStr, $provider, $location, $code);
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
            $slotSql = $this->query("SELECT user, project_id FROM em_scheduling_calendar WHERE id = ?", [$id]);
            if ($slotRow = db_fetch_assoc($slotSql)) {
                if (!$this->canManageAvailability($slotRow["user"], $slotRow["project_id"] ?? ($id_or_payload["pid"] ?? null)))
                    return [
                        "msg" => "Permission denied: Only calendar administrators or the provider can modify this availability",
                        "success" => false
                    ];
            }
            $newStart = $id_or_payload["start"];
            $newEnd = $id_or_payload["end"];
            $timezone = $id_or_payload["timezone"] ?? "local";
            $server_tz = date_default_timezone_get();
            if ($timezone != "local" && $timezone != $server_tz) {
                if ($newStart != null) {
                    $dtStart = new DateTime($newStart, new DateTimeZone($timezone));
                    $dtStart->setTimezone(new DateTimeZone($server_tz));
                    $newStart = $dtStart->format('Y-m-d H:i:s');
                }
                if ($newEnd != null) {
                    $dtEnd = new DateTime($newEnd, new DateTimeZone($timezone));
                    $dtEnd->setTimezone(new DateTimeZone($server_tz));
                    $newEnd = $dtEnd->format('Y-m-d H:i:s');
                }
            }
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
        $msg = "Availability $id updated to range $newStart to $newEnd";

        $this->log(
            "Modified Availability",
            [
                "agent" => $this->getSafeUser()?->getUsername(),
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
        $id = $payload["id"] ?? ($payload["internal_id"] ?? null);
        if ($id) {
            $slotSql = $this->query("SELECT user, project_id FROM em_scheduling_calendar WHERE id = ?", [$id]);
            if ($slotRow = db_fetch_assoc($slotSql)) {
                if (!$this->canManageAvailability($slotRow["user"], $slotRow["project_id"] ?? ($payload["pid"] ?? null)))
                    return [
                        "msg" => "Permission denied: Only calendar administrators or the provider can delete this availability",
                        "success" => false
                    ];
            }
        }
        if (isset($payload["start"]) && isset($payload["end"]) && isset($payload["id"]))
            return $this->deleteSplitAvailability($payload);
        if (isset($payload["start"]) && isset($payload["end"]))
            return $this->deleteRangeAvailability($payload);
        if (isset($payload["id"])) {
            // Can't log in delete entry as we won't even know if its Avail/Appt
            $result = $this->deleteEntry($payload);
            $this->log(
                "Deleted Availability Entry",
                [
                    "agent" => $this->getSafeUser()?->getUsername(),
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
        $timezone = $payload["timezone"] ?? "local";
        $server_tz = date_default_timezone_get();
        if ($timezone != "local" && $timezone != $server_tz) {
            $dtStart = new DateTime($start, new DateTimeZone($timezone));
            $dtStart->setTimezone(new DateTimeZone($server_tz));
            $start = $dtStart->format('Y-m-d H:i:s');
            $dtEnd = new DateTime($end, new DateTimeZone($timezone));
            $dtEnd->setTimezone(new DateTimeZone($server_tz));
            $end = $dtEnd->format('Y-m-d H:i:s');
        }

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
            "locations" => $location,
            "timezone" => "local"
        ]);

        $this->log(
            "Split Availability",
            [
                "agent" => $this->getSafeUser()?->getUsername(),
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
        $project_id = $payload["pid"] ?? null;

        if (!empty($providers) && $providers[0] === "*") {
            if (!$this->isCalendarAdmin($project_id))
                return [
                    "msg" => "Permission denied: Only calendar administrators can delete all providers' availability",
                    "success" => false
                ];
        } elseif (!empty($providers)) {
            foreach ((array)$providers as $prov) {
                if (!$this->canManageAvailability($prov, $project_id))
                    return [
                        "msg" => "Permission denied: Only calendar administrators or the provider can delete this availability range",
                        "success" => false
                    ];
            }
        }
        $timezone = $payload["timezone"] ?? "local";
        $server_tz = date_default_timezone_get();
        if ($timezone != "local" && $timezone != $server_tz) {
            $dtStart = new DateTime($start, new DateTimeZone($timezone));
            $dtStart->setTimezone(new DateTimeZone($server_tz));
            $start = $dtStart->format('Y-m-d H:i:s');
            $dtEnd = new DateTime($end, new DateTimeZone($timezone));
            $dtEnd->setTimezone(new DateTimeZone($server_tz));
            $end = $dtEnd->format('Y-m-d H:i:s');
        }

        $query = $this->createQuery();
        $query->add("DELETE FROM em_scheduling_calendar WHERE record IS NULL");

        if (!empty($codes) && $codes[0] != "*")
            $query->add("AND")->addInClause("availability_code", $codes);

        if (!empty($providers) && $providers[0] != "*")
            $query->add("AND")->addInClause("user", $providers);

        if (!empty($locations) && $locations[0] != "*")
            $query->add("AND")->addInClause("location", $locations);

        $query->add("AND time_start >= ? AND time_end <= ?", [$start, $end]);
        $query->execute();

        $this->log(
            "Deleted Availability Range",
            [
                "agent" => $this->getSafeUser()?->getUsername(),
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
        if (is_array($id))
            $id = $id["internal_id"] ?? $id["id"];
        if (empty($id))
            return [
                "msg" => "No id provided",
                "success" => false
            ];
        $result = $this->query("SELECT * FROm em_scheduling_calendar WHERE id = ?", [$id]);
        if ($result->num_rows == 0)
            return [
                "msg" => "No entry found for id $id",
                "success" => false
            ];
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
        if ($allFlag) {
            $user = $this->getSafeUser();
            $current_user = $user?->getUsername();
            if (!empty($current_user) && !$user->isSuperUser())
                $payload["providers"] = [$current_user];
        }
        $providers = $payload["providers"];
        $locations = $payload["locations"];
        $subjects = $payload["subjects"];
        $visits = $payload["visits"];
        $start = $payload["start"];
        $end = $payload["end"];
        $timezone = $payload["timezone"];
        $server_tz = date_default_timezone_get();
        $timezone = $timezone == $server_tz ? "local" : $timezone;

        $allUsers = $this->getAllUsers();
        $allSubjects = $allFlag ? $this->getGlobalSubjects($providers) : $this->getSubjects($payload);

        if (!$allFlag) {
            $allLocations = $this->getLocationStructure($project_id, true);
            $allVisits = $this->getVisits($payload);
        }

        $query = $this->createQuery();
        $query->add("SELECT * FROM em_scheduling_calendar WHERE record IS NOT NULL");

        if (!$allFlag)
            $query->add("AND project_id = ?", $project_id);

        if (!empty($providers))
            $query->add("AND")->addInClause("user", $providers);

        if (!empty($locations))
            $query->add("AND")->addInClause("location", $locations);

        if (!empty($subjects))
            $query->add("AND")->addInClause("record", $subjects);

        if (!empty($visits))
            $query->add("AND")->addInClause("visit", $visits);

        $query->add("AND time_start >= ? AND time_end <= ?", [$start, $end]);
        $result = $query->execute();

        $appt = [];
        while ($row = $result->fetch_assoc()) {
            $pid = $row["project_id"];
            if ($allFlag) {
                $allVisits = $this->getVisits(["pid" => $pid]);
                $allLocations = $this->getLocationStructure($pid, true);
            }
            $allSubjectsRecord = $allFlag ? "$row[project_id]:$row[record]" : $row["record"];
            $start = $row["time_start"];
            $end = $row["time_end"];
            if ($timezone != "local") {
                $dtStart = new DateTime($start, new DateTimeZone($server_tz));
                $dtStart->setTimezone(new DateTimeZone($timezone));
                $start = $dtStart->format('Y-m-d H:i:s');
                $dtEnd = new DateTime($end, new DateTimeZone($server_tz));
                $dtEnd->setTimezone(new DateTimeZone($timezone));
                $end = $dtEnd->format('Y-m-d H:i:s');
            }
            $appt[] = [
                "internal_id" => $row["id"],
                "project_id" => $pid,
                "title" => "Default Title",
                "start" => $start,
                "end" => $end,
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
        $timezone = $payload["timezone"];
        $server_tz = date_default_timezone_get();
        $timezone = $timezone == $server_tz ? "local" : $timezone;

        // Check for duration
        $config = $this->getVisits($payload)[$visit];
        if (!empty($config["duration"])) {
            $duration = (strtotime($end) - strtotime($start)) / 60;
            $msg = "";
            if ($config["isExtendable"] && ($duration < $config["duration"]))
                $msg = "Appointment duration must be at least $config[duration] minutes";
            if (!$config["isExtendable"] && ($duration != $config["duration"]))
                $msg = "Appointment duration must be exactly $config[duration] minutes";
            if ($msg)
                return [
                    "msg" => "Appointment duration must be exactly $config[duration] minutes",
                    "success" => false
                ];
        }

        if ($timezone != "local") {
            $dtStart = new DateTime($start, new DateTimeZone($timezone));
            $dtStart->setTimezone(new DateTimeZone($server_tz));
            $start = $dtStart->format('Y-m-d H:i:s');
            $dtEnd = new DateTime($end, new DateTimeZone($timezone));
            $dtEnd->setTimezone(new DateTimeZone($server_tz));
            $end = $dtEnd->format('Y-m-d H:i:s');
            $payload["start"] = $start;
            $payload["end"] = $end;
            $payload["timezone"] = "local";
        }

        // Search for availability that overflows the start/end
        $payload["allow_overflow"] = true;
        $existing = $this->getAvailability($payload);

        if (count($existing) == 0)
            return [
                "msg" => "Unable to add appointment, valid matching availability not found",
                "success" => false
            ];
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
                "timezone" => "local"
            ]);
        }

        // Create JSON with info for restoring avail if deleted
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
            "Appointment Scheduled",
            [
                "agent" => $this->getSafeUser()?->getUsername(),
                "provider" => $provider,
                "location" => $location,
                "start" => $start,
                "end" => $end,
                "record" => $record,
                "visit" => $visit,
                "notes" => $notes,
                "timezone" => "local" // Always store in local timezone
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

        // Preserve and update metadata restore parameters for updated provider/location
        $meta = $this->getRowMetadata($id);
        $metaJson = null;
        if (!empty($meta["restore"])) {
            $meta["restore"]["providers"] = $provider;
            $meta["restore"]["locations"] = $location;
            unset($meta["start"], $meta["end"]);
            $metaJson = json_encode($meta);
        }

        // Do the update
        $this->query(
            "UPDATE em_scheduling_calendar SET user = ?, location = ?, metadata = ? WHERE id = ?",
            [$provider, $location, $metaJson, $id]
        );

        $this->log(
            "Appointment Modified",
            [
                "agent" => $this->getSafeUser()?->getUsername(),
                "provider" => $provider,
                "location" => $location,
                "record" => $record,
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
        if (isset($payload["start"]) && isset($payload["end"]))
            return $this->deleteRangeAppointments($payload);

        if (!isset($payload["id"]))
            return [
                "msg" => "Unable to delete appointment, no database id provided",
                "success" => false
            ];

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
                "agent" => $this->getSafeUser()?->getUsername(),
                ...$result["data"]
            ]
        );

        return $result;
    }

    private function restoreAvailability($id)
    {
        $meta = $this->getRowMetadata($id);
        if (empty($meta["restore"]))
            return;
        $this->setAvailability(
            [
                ...$meta["restore"],
                "start" => $meta["start"],
                "end" => $meta["end"],
                "timezone" => "local"
            ],
            true
        );
    }

    private function deleteRangeAppointments($payload)
    {
        $start = $payload["start"];
        $end = $payload["end"];
        $subjects = $payload["subjects"];
        $project_id = $payload["pid"];
        $timezone = $payload["timezone"] ?? "local";
        $server_tz = date_default_timezone_get();
        if ($timezone != "local" && $timezone != $server_tz) {
            $dtStart = new DateTime($start, new DateTimeZone($timezone));
            $dtStart->setTimezone(new DateTimeZone($server_tz));
            $start = $dtStart->format('Y-m-d H:i:s');
            $dtEnd = new DateTime($end, new DateTimeZone($timezone));
            $dtEnd->setTimezone(new DateTimeZone($server_tz));
            $end = $dtEnd->format('Y-m-d H:i:s');
        }

        if (empty($subjects))
            return [
                "msg" => "Unable to delete appointment range, missing subject(s)",
                "success" => false
            ];

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
                "agent" => $this->getSafeUser()?->getUsername(),
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
        $project_id = $payload["pid"] ?? $this->getProjectId();
        if (!$this->isCalendarAdmin($project_id))
            return [
                "msg" => "Permission denied: Only calendar administrators can modify user colors/metadata",
                "success" => false
            ];
        $meta = $payload["metadata"];
        $this->setProjectSetting("user-metadata", json_encode($meta), $project_id);
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
            "all_appointments" => false,
            "timezone" => "local"
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
            foreach ($desc as $title => $value)
                $text = "{$text}{$title}: $value\\n";

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
        if (!$data_entry_trigger_enabled || $data_entry_trigger_url == '')
            return false;

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
        if (substr($data_entry_trigger_url, 0, 1) == "/")
            $pre_url = (SSL ? "https://" : "http://") . SERVER_NAME;
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
            foreach ($event_data as $event_id => $event_fields) {
                foreach ($event_fields as $field => $value) {
                    if (!isset($results[$record_id][$field]) || ($value !== '' && $value !== null))
                        $results[$record_id][$field] = $this->escape($value);
                }
            }
        }
        return $results;
    }
}
