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
 * Auth method health check.
 *
 * @package    tool_heartbeat
 * @copyright  2021 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This can be run either as a web api, or on the CLI. When run on the
 * CLI it conforms to the Nagios plugin standard.
 *
 * See also:
 *  - http://nagios.sourceforge.net/docs/3_0/pluginapi.html
 *  - https://nagios-plugins.org/doc/guidelines.html#PLUGOUTPUT
 */

namespace tool_heartbeat\check;
use core\check\check;
use core\check\result;

/**
 * Auth check class.
 *
 * @copyright  2022
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class authcheck extends check {

    /**
     * Get an action link.
     *
     * @return null|\action_link
     */
    public function get_action_link(): ?\action_link {
        $url = new \moodle_url('/admin/settings.php', ['section' => 'manageauths']);
        return new \action_link($url, get_string('authsettings', 'admin'));
    }

    /**
     * Get Result.
     *
     * @return result
     */
    public function get_result(): result {
        global $DB;

        // Value must be raw DB value. We will see it here before in settings cache.
        $value = $DB->get_field('config', 'value', ['name' => 'auth']);
        // Hacky config field to store previous state.
        $prev = get_config('tool_heartbeat', 'authstate');

        if ($prev === false) {
            // We don't know the status of auth methods before now.
            // Set and forget.
            set_config('authstate', $value, 'tool_heartbeat');
            return new result(result::WARNING, get_string('setinitialauthstate', 'tool_heartbeat'));
        }

        // Now check if the value has actually changed. An empty is valid if it was the initial state,
        // But it should still be a warning if the catalyst methods aren't set.

        // Empty, when it wasn't empty previously.
        $critical = empty($value) && $value !== $prev;
        // Warning if there is no configured methods in the string at all.
        $config = get_config('tool_heartbeat', 'configuredauths');

        $methods = !empty($config) ? explode(',', $config) : [];
        $warning = false;
        foreach ($methods as $method) {
            if (stripos($value, $method) === false) {
                $warning = true;
                break;
            }
        }

        if (!$critical && $value !== $prev) {
            // If it wasn't a critical, we should update the current state.
            set_config('authstate', $value, 'tool_heartbeat');
        }

        if ($critical) {
            $status = result::CRITICAL;
            $summary = get_string('emptyautherror', 'tool_heartbeat', $prev);
        } else if ($warning) {
            $status = result::WARNING;
            $summary = get_string('configauthmissing', 'tool_heartbeat');
        } else {
            $status = result::OK;
            $summary = get_string('authcorrect', 'tool_heartbeat');
        }
        return new result($status, $summary);
    }
}
