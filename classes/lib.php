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
    public static function get_allowed_ips(): string {
        return trim(
            get_config('tool_heartbeat', 'allowedips') .
            PHP_EOL .
            get_config('tool_heartbeat', 'allowedips_forced')
        );
    }
}
