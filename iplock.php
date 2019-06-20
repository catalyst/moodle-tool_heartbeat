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
 * Validates any remote IP connected to a page calling this script, and checks
 * against current configs in plugin settings.
 *
 * @param string List of IPs to validate remote IP against
 * @return null Returns to calling class if remote IP is in safe list, or safe list is empty
 *  
 */
function validate_ip_against_config($iplist) {
    // Validate remote IP against safe list.
    if (remoteip_in_list($iplist)) {
        return;
    } else if (trim($iplist) == '') {
        return;
    } else {
        header("HTTP/1.0 403 Forbidden");
        exit;
    }
}

