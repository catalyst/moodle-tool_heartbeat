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
 * @package    tool_heartbeat
 * @copyright  2014 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Set this manually to true as needed
if (false){
    print "Server is in MAINTENACE";
    exit;
}


if ($argv[0]){
    define('CLI_SCRIPT', true);
} else {
    define('NO_MOODLE_COOKIES', true);
}
define('NO_UPGRADE_CHECK', true);
define('ABORT_AFTER_CONFIG', true);

require('../../../config.php');
global $DB, $CFG;

$status = "";

function failed($reason) {
    //Status for ELB, will cause ELB to remove instance.
    header("HTTP/1.0 503 Service unavailable: failed $reason check");
    //Status for the humans
    print "Server is DOWN<br>\n";
    echo "Failed: $reason";
    exit;
}

if(file_exists($CFG->dataroot . "/elb.test")) {
    $status .= "sitedata OK<br>\n";
} else {
    failed('sitedata');
}


$session_handler = (property_exists($CFG, 'session_handler_class') && $CFG->session_handler_class == '\core\session\memcached');

if ($session_handler){

    $memcache = explode(':', $CFG->session_memcached_save_path );
    try {
        memcache_connect($memcache[0], $memcache[1], 3);
        $status .= "session memcache OK<br>\n";
    } catch (Exception $e){
        failed('sessions memcache');
    }
}

print "Server is ALIVE<br>\n";
print $status;

