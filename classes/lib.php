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

namespace tool_heartbeat;

/**
 * General functions for use with heartbeat.
 *
 * @package   tool_heartbeat
 * @author    Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2022, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib {
    /**
     * Return the list of allowed IPs, by combining the UI editable IP list with the
     * config defined IP list.
     *
     * @return string
     */
    public static function get_allowed_ips() {
        return trim(
            get_config('tool_heartbeat', 'allowedips') .
            PHP_EOL .
            get_config('tool_heartbeat', 'allowedips_forced')
        );
    }

    /**
     * Validates any remote IP connected against the IP list stored as config.
     *
     * @return null Returns to calling class if remote IP is in safe list, or safe list is empty
     *
     */
    public static function validate_ip_against_config() {
        $iplist = self::get_allowed_ips();
        // Require library for nagios responses.
        require_once(__DIR__.'/../nagios.php');
        // Validate remote IP against safe list.
        if (remoteip_in_list($iplist)) {
            return;
        } else if (trim($iplist) == '') {
            return;
        } else {
            $msg = 'Failed IP check from '.getremoteaddr();
            send_unknown($msg);
        }
    }
}
