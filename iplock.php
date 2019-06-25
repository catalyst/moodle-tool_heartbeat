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
 * against current configs in plugin settings. Requires config.php to be loaded before including this script
 *
 * @param string  $iplist List of IPs to validate remote IP against
 * @param bool $icinga Specifies whether the validation should return an Icinga response, true for icinga, false for HTTP
 * @return null Returns to calling class if remote IP is in safe list, or safe list is empty
 *
 */
// @codingStandardsIgnoreStart
// Ignore required to skip codechecker error for no config.php load in class
function validate($iplist, $icinga) {
// @codingStandardsIgnoreEnd
    // Validate remote IP against safe list.
    if (remoteip_in_list($iplist)) {
        return;
    } else if (trim($iplist) == '') {
        return;
    } else {
        if ($icinga) {
            printf('UNKNOWN: Failed IP check from '.getremoteaddr());
            exit;
        } else {
            header("HTTP/1.0 403 Forbidden");
            print('<h1> IP FORBIDDEN </h1>');
            exit;
        } 
    }
}

/**
 * Wrapper function for easy access to the validation, outside functions should call here.
 *
 * @param bool Specifies whether the validation should return an Icinga response, true for icinga, false for HTTP, defaults to false
 *
 */
function validate_ip_against_config($icinga = false) {
    validate(get_config('tool_heartbeat', 'allowedips'), $icinga);
}

