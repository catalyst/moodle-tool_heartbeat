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
 * Check if session has persisted from request host and report diagnostic information.
 *
 * @package    tool_heartbeat
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

global $SESSION;

$testnumber = required_param('testnumber', PARAM_INT);
$requesttimemicros = required_param('reqtime', PARAM_FLOAT);
$requesthost = required_param('host', PARAM_TEXT);

$result = new stdClass();

$currenttimemicros = microtime(true);
$result->latency = $currenttimemicros - $requesttimemicros;
$result->requesthost = $requesthost;
$result->responsehost = gethostname();
$result->requesttime = $requesttimemicros;
$result->responsetime = $currenttimemicros;


if ($SESSION->testnumber === $testnumber) {
    $result->success = 'pass';
} else {
    $result->success = 'fail';
}

unset($SESSION->testnumber);
echo json_encode($result);
