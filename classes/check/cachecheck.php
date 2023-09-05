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
 * Cache split check.
 *
 * @package    tool_heartbeat
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT 2023
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
 * Cache check class
 *
 * This detects some split brain cache setups
 *
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT 2023
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cachecheck extends check {

    /**
     * Get Result.
     *
     * @return result
     */
    public function get_result() : result {
        global $DB;

        $status = result::OK;
        $summary = '';
        $details = '';

        $results = $this->check('web');
        $results += $this->check('cron');

        if (empty($results['webapi'])) {
            $status = result::CRITICAL;
            $summary = get_string('checkcachewebmissing', 'tool_heartbeat');
        } else if (!$results['cronapi']) {
            $status = result::CRITICAL;
            $summary = get_string('checkcachecronmissing', 'tool_heartbeat');
        } else {

            // This checks if the cron and web are not recently pinged.
            if ($results['webapi'] != $results['webdb']) {
                $status = result::CRITICAL;
                $summary .= get_string('checkcacheerrorsplit', 'tool_heartbeat', ['type' => 'web']);
            }
            if ($results['cronapi'] != $results['crondb']) {
                $status = result::CRITICAL;
                $summary .= get_string('checkcacheerrorsplit', 'tool_heartbeat', ['type' => 'cron']);
            }
        }

        if ($status == result::OK) {
            $summary = get_string('checkcachenotsplit', 'tool_heartbeat');
        } else {
            $details = get_string('checkcachedetails', 'tool_heartbeat');
        }

        $details .= '<table class="table table-sm w-auto table-bordered">';
        foreach ($results as $key => $value) {
            $details .= \html_writer::start_tag('tr');
            $details .= \html_writer::tag('td', $key);
            $details .= \html_writer::tag('td', $value);
            $details .= \html_writer::tag('td', userdate($value));
            $details .= \html_writer::end_tag('tr');
        }
        $details .= '</table>';
        return new result($status, $summary, $details);
    }

    /**
     * Get the ping values from the cache and db to compare
     */
    public function check($type) {
        global $DB;

        $return = [];
        $key = "checkcache{$type}ping";
        $return[$type . 'api'] = get_config('tool_heartbeat', $key);
        $return[$type . 'db'] = $DB->get_field('config_plugins', 'value', [
            'plugin' => 'tool_heartbeat',
            'name'  => $key,
        ]);
        return $return;
    }

    /**
     * Sets a timestamp in config from web or cron
     */
    public static function ping($type) {
        $key = "checkcache{$type}ping";
        $current = get_config('tool_heartbeat', $key);

        // Only update if the currently cached time is very old.
        if ($current < (time() - DAYSECS)) {
print "doing $type ping $current ";
            set_config($key, time(), 'tool_heartbeat');
        }
    }

}
