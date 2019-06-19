<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upload speed check
 *
 * @package    tool_heartbeat
 * @copyright  2017 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This is an upload handler which accepts data and then reports on how long
 * it took. It can be used standalone or in conjunction with:
 *
 * php cli/testupload.php
 *
 */

 // Get plugin config for IP validating
$dirroot = '../../../';
require($dirroot.'config.php');

include('iplock.php');

// IP Locking, check for remote IP in validated list, make sure not run from CLI, if not, exit    $allowedips = get_config('tool_heartbeat','ipconfig');
$allowedips = get_config('tool_heartbeat','ipconfig');
if ((!(validate_IP_against_config($allowedips))) && !(isset($argv))){
    header("HTTP/1.0 403 Forbidden");
    exit;
}

$starttime = microtime(true);

$putdata = fopen("php://input", "r");
$totalbytes = 0;
while ($data = fread($putdata, 1024 * 4)) {
    $size = strlen($data);
    $totalbytes += $size;
}
fclose($putdata);

$endtime = microtime(true);

$duration = $endtime - $starttime; // In seconds.

printf("Size = %.1fMB, Time = %.3fs,  %.1fMbps ",
    $totalbytes / 1024 / 1024,
    $duration,
    $totalbytes * 8 / $duration / 1000 / 1000
);

