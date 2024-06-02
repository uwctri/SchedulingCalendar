<?php
// API for downloading the ICS file
// GET /api/?NOAUTH&type=module&prefix=scheduling_calendar&page=ics&pid={}&hash={}
$project_id = $_GET['pid'];
$hash = $_GET['hash'];
if (!isset($hash) || !isset($project_id))
    exit();
$json = json_decode($module->getProjectSetting("ics-hash-json", $project_id) ?? "{}", true);
$user = $json[$hash];
if (empty($user))
    exit();
$users = $module->getProjectSetting("calendar-admin", $project_id) ?? [];
if (!in_array($user, $users))
    exit();
$ics = $module->makeICS($project_id);
header("Content-disposition: attachment; filename=\"{$module->getProjectName()} Calendar.ics\"");
header("Content-Type: text/calendar");
echo $ics;
