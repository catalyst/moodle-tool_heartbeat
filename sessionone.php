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
 * Set a random integer in session and redirect to check if persists.
 *
 * @package    tool_heartbeat
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// @codingStandardsIgnoreStart
require_once('../../../config.php');
// @codingStandardsIgnoreEnd
tool_heartbeat\lib::validate_ip_against_config();

global $SESSION;

$testnumber = rand();
$testtimemicro = microtime(true);
$hostname = gethostname();

$SESSION->testnumber = $testnumber;

$params = [
    'testnumber' => $testnumber,
    'reqtime' => $testtimemicro,
    'host' => $hostname];
$url = new moodle_url('/admin/tool/heartbeat/sessiontwo.php', $params);

redirect($url);
