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
 * Returns a Nagios status code, used for automated monitoring
 *
 * Numeric value:   |   Status  |   Description
 * 0                |    OK     |   The plugin was able to check the service and
 *                              |   it appeared to be functioning properly.
 * 1                |  Warning  |   The plugin was able to check the service, but
 *                              |   it appeared to be above some "warning"
 *                              |   threshold or did not appear to be working properly.
 * 2                |  Critical |   The plugin detected that either the service was
 *                              |   not running or it was above some "critical" threshold.
 * 3                |  Unknown  |   The plugin was unable to determine the status of the service.
 *
 * @package    tool_heartbeat
 * @copyright  2019 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// @codingStandardsIgnoreStart
// Ignore required to skip codechecker error for no config.php load in class
$format = '%b %d %H:%M:%S';
// @codingStandardsIgnoreEnd
$now = userdate(time(), $format);

/**
 * Sends a good Nagios response, with message.
 *
 * @param string  $msg the message to append the Nagios response.
 * @param string  $more aditional message
 */

function send_good($msg, $more = '') {
    global $now;
    printf ("OK: $msg (Checked $now)\n$more");
    exit(0);
}

/**
 * Sends a warning Nagios response, with message.
 *
 * @param string  $msg the message to append the Nagios response.
 * @param string  $more aditional message
 */

function send_warning($msg, $more = '') {
    global $now;
    printf ("WARNING: $msg (Checked $now)\n$more");
    exit(1);
}

/**
 * Sends a critical Nagios response, with message.
 *
 * @param string  $msg the message to append the Nagios response.
 * @param string  $more aditional message
 */

function send_critical($msg, $more = '') {
    global $now;
    printf ("CRITICAL: $msg (Checked $now)\n$more");
    exit(2);
}

/**
 * Sends an unknown Nagios response, with message.
 *
 * @param string  $msg the message to append the Nagios response.
 * @param string  $more aditional message
 */

function send_unknown($msg, $more = '') {
    printf ("UNKNOWN: $msg\n$more");
    exit(3);
}

/**
 * This converts a check to the nagios web format
 *
 * @param $check
 */
function send_check($check) {

    $result = $check->get_result();
    if ($result->status == \core\check\result::OK) {
        send_good($result->get_summary(), $result->get_details());
    }
    if ($result->status == \core\check\result::WARNING) {
        send_warning($result->get_summary(), $result->get_details());
    }
    send_critical($result->get_summary(), $result->get_details());

}

