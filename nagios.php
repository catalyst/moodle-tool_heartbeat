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
// Ignore required to skip codechecker error for no config.php load in class.
require_once(__DIR__ . '/../../../config.php');
// @codingStandardsIgnoreEnd
$format = '%b %d %H:%M:%S';
$now = userdate(time(), $format);


/**
 * Sends a good Nagios response, with message.
 *
 * @param string  $msg the message to append the Nagios response.
 * @param string  $more aditional message
 */
function send_good($msg, $more = '') {
    send(0, $msg, $more);
}

/**
 * Sends a warning Nagios response, with message.
 *
 * @param string  $msg the message to append the Nagios response.
 * @param string  $more aditional message
 */
function send_warning($msg, $more = '') {
    send(1, $msg, $more);
}

/**
 * Sends a critical Nagios response, with message.
 *
 * @param string  $msg the message to append the Nagios response.
 * @param string  $more aditional message
 */
function send_critical($msg, $more = '') {
    send(2, $msg, $more);
}

/**
 * Sends an unknown Nagios response, with message.
 *
 * @param string  $msg the message to append the Nagios response.
 * @param string  $more aditional message
 */
function send_unknown($msg, $more = '') {
    send(3, $msg, $more);
}

/**
 * Sends a Nagios response, with message.
 * @param int $level Status code level
 * @param string $msg the message to append the Nagios response.
 * @param string $more additional message
 */
function send($level, $msg, $more = '') {
    global $now;
    $buffercontents = check_buffer();

    // If buffer was outputted, and is currently OK or UNKNOWN level, upgrade to WARNING level.
    if (!empty($buffercontents) && ($level == 0 || $level == 3)) {
        $level = 1;
    }

    $prefixes = [
        0 => "OK",
        1 => "WARNING",
        2 => "CRITICAL",
        3 => "UNKNOWN",
    ];

    // Add any buffer contents message to the msg, ensuring the details are on the first line.
    $msglines = explode("\n", $msg);

    $msglines[0] .= $buffercontents;
    $msg = implode("\n", $msglines);

    printf("{$prefixes[$level]}: $msg (Checked {$now})\n$more");
    exit($level);
}

/**
 * Finishes output buffering, and returns a message if the buffer contained anything.
 * Ideally, the buffer should always be empty, but debugging messages often are outputted to it.
 * @return string
 */
function check_buffer() {
    $contents = ob_get_clean();

    // All good - no output.
    if ($contents == false) {
        return '';
    }

    // There was output, return it.
    return ", but there was unexpected output:\n {$contents}\n";
}

/**
 * This converts a check to the nagios web format
 *
 * @param \core\check\check $check
 * @return void
 */
function send_check(\core\check\check $check): void {

    $result = $check->get_result();
    if ($result->status == \core\check\result::OK) {
        send_good($result->get_summary(), $result->get_details());
    }
    if ($result->status == \core\check\result::WARNING) {
        send_warning($result->get_summary(), $result->get_details());
    }
    send_critical($result->get_summary(), $result->get_details());

}

