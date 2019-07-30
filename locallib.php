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

use core\session\util;

/**
 * This file contains functions used by tool_heartbeat.
 *
 * @package    tool_heartbeat
 * @copyright  2019 Tom Dickman <tomdickman@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Checks if the command line maintenance mode has been enabled. Skip the config bootstrapping.
 *
 * @param string $configfile The relative path for config.php
 * @return bool True if climaintenance.html is found.
 */
function check_climaintenance($configfile) {
    $content = file_get_contents($configfile);
    $content = preg_replace("#[^!:]//#", "\n//", $content);  // Set comments to be on newlines, replace '//' with '\n//', where // does not start with :
    $content = preg_replace("/;/", ";\n", $content);         // Split up statements, replace ';' with ';\n'
    $content = preg_replace("/^[\s]+/m", "", $content);      // Removes all initial whitespace and newlines.

    $re = '/^\$CFG->dataroot\s+=\s+["\'](.*?)["\'];/m';  // Lines starting with $CFG->dataroot
    preg_match($re, $content, $matches);
    if (!empty($matches)) {
        $climaintenance = $matches[count($matches) - 1] . '/climaintenance.html';

        if (file_exists($climaintenance)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if configured redis instance is working by connecting and storing a key => value
 * based on date, then retrieving it to make sure it's the same.
 *
 * @return bool true if check passed, false otherwise.
 */
function tool_heartbeat_redis_check() {
    global $CFG;
    $redistestkey = 'redis_test_key:' . date(DATE_ATOM);
    $redistestvalue = 'redis_test_value:' .date(DATE_ATOM);

    // Build a standalone redis connection to the store to remove Moodle APIs as a potential factor.
    try {
        $redis = new Redis();
        $redis->connect($CFG->session_redis_host, $CFG->session_redis_port, 2);
        if (isset($CFG->session_redis_auth) && !empty($CFG->session_redis_auth)) {
            $redis->auth($CFG->session_redis_auth);
        }// Ping the server first.
        $ping = $redis->ping();
        if ($ping == '+PONG') {
            // Store a value and retrieve, checking it's the same.
            $redis->set($redistestkey, $redistestvalue);
            if ($redis->get($redistestkey) === $redistestvalue) {
                return true;
            }
        }
        $redis->close();
        return false;
    } catch (Exception $e) {
        debugging($e->getMessage());
        return false;
    }
}
