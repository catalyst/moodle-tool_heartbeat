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
 * Callback point.
 *
 * @package    tool_heartbeat
 * @copyright  2021 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs before HTTP headers. Used to ping the cachecheck.
 */
function tool_heartbeat_before_http_headers() {
    if (class_exists('\core\check\manager')) {
        \tool_heartbeat\check\cachecheck::ping('web');
    }
}

/**
 * Status checks.
 *
 * @return array
 */
function tool_heartbeat_status_checks() {
    $checks = [
        new \tool_heartbeat\check\authcheck(),
        new \tool_heartbeat\check\cachecheck(),
        new \tool_heartbeat\check\logstorecheck(),
        new \tool_heartbeat\check\tasklatencycheck(),
        new \tool_heartbeat\check\failingtaskcheck(),
    ];
    $taskchecks = \tool_heartbeat\check\failingtaskcheck::get_failing_tasks();
    return array_merge($checks, $taskchecks);
}

/**
 * Performance checks.
 *
 * @return array
 */
function tool_heartbeat_performance_checks() {
    return [
        new \tool_heartbeat\check\rangerequestcheck(),
        new \tool_heartbeat\check\dirsizes(),
    ];
}

/**
 * Security checks.
 *
 * @return array
 */
function tool_heartbeat_security_checks() {
    return [
        new \tool_heartbeat\check\dnscheck(),
    ];
}

/**
 * Serves test files for heartbeat.
 *
 * @param stdClass $course Course object
 * @param stdClass $cm Course module object
 * @param context $context Context
 * @param string $filearea File area for data privacy
 * @param array $args Arguments
 * @param bool $forcedownload If we are forcing the download
 * @param array $options More options
 * @return bool Returns false if we don't find a file.
 */
function tool_heartbeat_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG;

    // README is just safe content we know exist. Used in the range request check.
    $file = "$CFG->dirroot/admin/tool/heartbeat/README.md";
    readfile_accel($file, 'text/plain', true);
    die;
}
