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
 * @copyright  2019 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Sends a good Icinga response, with message.
 *
 * @param string  $msg the message to append the Icinga response.
 */

function send_good($msg) {
    global $now;
    printf ("OK: $msg (Checked $now)\n");
    exit(0);
}

/**
 * Sends a warning Icinga response, with message.
 *
 * @param string  $msg the message to append the Icinga response.
 */

function send_warning($msg) {
    global $now;
    printf ("WARNING: $msg (Checked $now)\n");
    exit(1);
}

/**
 * Sends a critical Icinga response, with message.
 *
 * @param string  $msg the message to append the Icinga response.
 */

function send_critical($msg) {
    global $now;
    printf ("CRITICAL: $msg (Checked $now)\n");
    exit(2);
}

/**
 * Sends an unknown Icinga response, with message.
 *
 * @param string  $msg the message to append the Icinga response.
 */

function send_unknown($msg) {
    printf ("UNKNOWN: $msg\n");
    exit(3);
}

