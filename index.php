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
 * Are you Ok? heartbeat for load balancers
 *
 * @package    tool
 * @subpackage heartbeat
 * @copyright  2014 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_UPGRADE_CHECK', true);

require('../../../config.php');

$status = "";

function failed($reason) {
    //Status for ELB, will cause ELB to remove instance.
    header("HTTP/1.0 503 Service unavailable: failed $reason check");
    //Status for the humans
    echo "FAILED HEALTH CHECK ($reason)";
    exit;
}


if(file_exists($CFG->dataroot . "/elb.test")) {
    $status .= "Sitedata OK <br>\n";
} else {
    failed('sitedata');
}

global $DB;
try {
    $record = $DB->get_record('config', array('name' => 'version'));
    $status .= "Database OK<br>\n";
} catch (Exception $e) {
    failed('database');
}

// This checks memcache connection
// memcache details
// $memcache_host = '138.77.0.107';
// $memcache_port = '11211';
// if(memcache_connect($memcache_host, $memcache_port, 3)) {
//     $status .= "Memcache OK <br>\n";
// } else {
//     failed('memcache');
// }

print $status;


