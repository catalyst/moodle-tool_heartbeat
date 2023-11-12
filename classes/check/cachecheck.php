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

namespace tool_heartbeat\check;
use core\check\check;
use core\check\result;

/**
 * Cache check class
 *
 * This detects some split brain cache setups
 *
 * @package    tool_heartbeat
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
        $results = $this->check('web');
        $results += $this->check('cron');

        list($status, $summary) = $this->build_result($results);

        $details = '';

        if ($status != result::OK) {
            $details .= get_string('checkcachedetails', 'tool_heartbeat');
        }

        $details .= '<table class="table table-sm w-auto table-bordered">';

        foreach ($results as $key => $value) {
            $details .= \html_writer::start_tag('tr');
            $details .= \html_writer::tag('td', $key);
            $details .= \html_writer::tag('td', $value);

            // Use DATE_RSS to show seconds, as well as timezone.
            $details .= \html_writer::tag('td', date(DATE_RSS, $value));
            $details .= \html_writer::end_tag('tr');
        }
        $details .= '</table>';
        return new result($status, $summary, $details);
    }

    /**
     * Reads the results and buils a check API result.
     * @param array $results from check() function.
     * @return array of [result status, summary string]
     */
    private function build_result(array $results): array {
        // Nothing set for web API.
        if (empty($results['webapi'])) {
            return [result::CRITICAL, get_string('checkcachewebmissing', 'tool_heartbeat')];
        }

        // Nothing set for cron API.
        if (empty($results['cronapi'])) {
            return [result::CRITICAL, get_string('checkcachecronmissing', 'tool_heartbeat')];
        }

        // Check for split cron cache/db, web cache/db, and all of them together.
        $cronsplit = $results['cronapi'] != $results['crondb'];
        $websplit = $results['webapi'] != $results['webdb'];

        if ($cronsplit || $websplit) {
            $splits = [
                'cron' => $cronsplit,
                'web' => $websplit,
            ];
            $splits = implode(",", array_keys(array_filter($splits)));

            return [result::CRITICAL, get_string('checkcacheerrorsplit', 'tool_heartbeat', $splits)];
        }

        // Else OK.
        return [result::OK, get_string('checkcachenotsplit', 'tool_heartbeat')];
    }

    /**
     * Get the ping values from the cache and db to compare
     * @param string $type type of check (e.g. web, cron)
     */
    public function check($type) {
        global $DB;

        $return = [];
        $key = "checkcache{$type}ping";

        // Read from cache (e.g. get_config uses cache).
        $return[$type . 'api'] = get_config('tool_heartbeat', $key);

        // Read directly from database.
        $return[$type . 'db'] = $DB->get_field('config_plugins', 'value', [
            'plugin' => 'tool_heartbeat',
            'name'  => $key,
        ]);
        return $return;
    }

    /**
     * Sets a timestamp in config from web or cron
     * @param string $type type of check (e.g. web, cron)
     */
    public static function ping($type) {
        $key = "checkcache{$type}ping";
        $current = get_config('tool_heartbeat', $key);

        // Only update if the currently cached time is very old.
        if ($current < (time() - DAYSECS)) {
            debugging("\nHEARTBEAT doing {$type} ping {$current}\n", DEBUG_DEVELOPER);
            set_config($key, time(), 'tool_heartbeat');
        }
    }
}
