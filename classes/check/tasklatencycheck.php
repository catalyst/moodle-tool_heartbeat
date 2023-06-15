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
 * Individual cron task latency monitoring check check.
 *
 * @package    tool_heartbeat
 * @copyright  2022
 * @author     Peter Burnett <peterburnett@catalyst-au.net>
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

defined('MOODLE_INTERNAL') || die();

/**
 * Task latency check class.
 *
 * @copyright  2022
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tasklatencycheck extends check {

    /**
     * Get the status of the monitored tasks.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG, $DB;

        $taskconfig = get_config('tool_heartbeat', 'tasklatencymonitoring');
        $status = result::OK;
        $okmessage = get_string('tasklatencyok', 'tool_heartbeat');
        $messages = '';

        if ($taskconfig === '') {
            return new result($status, $okmessage);
        }

        $lockstats = isset($CFG->lock_factory) && $CFG->lock_factory === \tool_lockstats\proxy_lock_factory::class;

        $tasks = explode(PHP_EOL, $taskconfig);

        foreach ($tasks as $taskconfig) {
            $configarr = explode(',', str_replace(' ', '', $taskconfig));
            if (count($configarr) !== 4) {
                return new result(result::ERROR, get_string('taskconfigbad', 'tool_heartbeat', $configarr[0]));
            }

            list($taskclass, $runtime, $startdelay, $completiondelay) = $configarr;

            $task = \core\task\manager::get_scheduled_task($taskclass);

            // Input validation.
            $valid = $task !== false;

            // Cast to int, will force non-int strings to 0, so we only need to care about negative time as invalid.
            $valid &= ((int) $runtime >= 0);
            $valid &= ((int) $startdelay >= 0);
            $valid &= ((int) $completiondelay >= 0);

            if (!$valid) {
                return new result(result::ERROR, get_string('taskconfigbad', 'tool_heartbeat', $taskclass));
            }

            // Let's see if the task is currently running. We can only do this via lockstats.
            if ($lockstats) {
                $locked = $DB->record_exists('tool_lockstats_locks', ['resourcekey' => $taskclass]);
            } else {
                $locked = false;
            }

            // Now we can start checking all of the data against the task itself.
            // First check for start time drift. This only matters if the task is not currently running.
            $starttime = $task->get_next_run_time();
            if (!$locked) {
                if ($startdelay != 0 && ($starttime <= (time() - $startdelay * MINSECS))) {
                    $status = result::CRITICAL;
                    $messages .= get_string(
                        'latencydelayedstart',
                        'tool_heartbeat',
                        ['task' => $taskclass, 'mins' => $startdelay]
                    ) . PHP_EOL;
                }
            }

            // Now check for large delays in the last completion.
            $lastrun = $task->get_last_run_time();
            // Again check if we are locked here.
            // If we ARE locked, we will alert for that instead in the next block.
            if (!$locked) {
                // If we aren't locked something deeper is wrong, cron might be cooked.
                if ($lastrun != 0 && $lastrun <= (time() - $completiondelay * MINSECS)) {
                    $status = result::CRITICAL;
                    $messages .= get_string(
                        'latencynotrun',
                        'tool_heartbeat',
                        ['task' => $taskclass, 'mins' => $startdelay]
                    ) . PHP_EOL;
                }
            }

            if ($runtime == 0) {
                // We dont need to go any further.
                continue;
            }

            // Now we should check the duration of the last run, whenever it was.
            $dbman = $DB->get_manager();
            $table = new \xmldb_table('task_log');
            if ($dbman->table_exists($table)) {
                // We can use task logs!
                $sql = "SELECT (timeend - timestart) AS duration
                          FROM {task_log}
                         WHERE classname = ?
                      ORDER BY timeend DESC
                         LIMIT 1";
                $record = $DB->get_record_sql($sql, [$taskclass]);

                if ($record && $record->duration > $runtime * MINSECS) {
                    $status = result::CRITICAL;
                    $messages .= get_string(
                        'latencyruntime',
                        'tool_heartbeat',
                        ['task' => $taskclass, 'mins' => $startdelay]
                    ) . PHP_EOL;
                }
            } else if ($lockstats) {
                // Our fallback here is lockstats.
                $sql = "SELECT duration
                          FROM {tool_lockstats_history}
                         WHERE classname = ?
                      ORDER BY released DESC
                         LIMIT 1";
                $record = $DB->get_record_sql($sql, [$taskclass]);

                if ($record && $record->duration > $runtime * MINSECS) {
                    $status = result::CRITICAL;
                    $messages .= get_string(
                        'latencyruntime',
                        'tool_heartbeat',
                        ['task' => $taskclass, 'mins' => $startdelay]
                    ) . PHP_EOL;
                }
            }
        }

        if ($status === result::OK) {
            return new result($status, $okmessage);
        }

        return new result($status, $messages);
    }

}
