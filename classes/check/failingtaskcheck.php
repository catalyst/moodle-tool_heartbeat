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
 * Task fail delay check
 *
 * This is very similar to the core tool_task::maxfaildelay check, except the output aggregates the number
 * of each task, so if you have thousands of a task failing it does not spam the output.
 *
 * @package    tool_heartbeat
 * @copyright  2023 Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class failingtaskcheck extends check {

    /** @var int $warnthreshold Threshold in minutes after which should warn about tasks failing **/
    public $warnthreshold = 60;

    /** @var int $errorthreshold Threshold in minutes after which should error about tasks failing **/
    public $errorthreshold = 600;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'cronfailingtasks';
        $this->name = get_string('checkfailingtaskcheck', 'tool_heartbeat');

        $this->actionlink = new \action_link(
            new \moodle_url('/admin/tasklogs.php'),
            get_string('tasklogs', 'tool_task'));
    }

    /**
     * Return result
     * @return result
     */
    public function get_result() : result {
        global $DB;

        $taskoutputs = [];

        // Instead of using task API here, we read directly from the database.
        // This stops errors originating from broken tasks.
        $scheduledtasks = $DB->get_records_sql("SELECT * FROM {task_scheduled} WHERE faildelay > 0 AND disabled = 0");

        foreach ($scheduledtasks as $task) {
            $taskoutputs[] = "SCHEDULED TASK: {$task->classname} Delay: {$task->faildelay}\n";
        }

        // Instead of using task API here, we read directly from the database.
        // This stops errors originating from broken tasks, and allows the DB to de-duplicate them.
        $adhoctasks = $DB->get_records_sql("  SELECT classname, COUNT(*) count, MAX(faildelay) faildelay, SUM(faildelay) cfaildelay
                                               FROM {task_adhoc}
                                              WHERE faildelay > 0
                                           GROUP BY classname
                                           ORDER BY cfaildelay DESC");

        foreach ($adhoctasks as $record) {
            // Only add duplicate message if there are more than 1.
            $duplicatemsg = $record->count > 1 ? " ({$record->count} duplicates!!!)" : '';
            $taskoutputs[] = "ADHOC TASK: {$record->classname} Delay: {$record->faildelay} {$duplicatemsg}\n";
        }

        // Find the largest faildelay out of both adhoc and scheduled tasks.
        $alldelays = array_merge(array_column($adhoctasks, 'faildelay'), array_column($scheduledtasks, 'faildelay'));
        $maxdelaymins = !empty($alldelays) ? max($alldelays) / 60 : 0;

        // Define a simple function to work out what the message should be based on the task outputs.
        // Returns the [$summary, $details].
        $taskoutputfn = function($faildelaymins) use ($taskoutputs) {
            $count = count($taskoutputs);

            if ($count == 1) {
                // Only a single task is failing, so put it at the top level.
                return [$taskoutputs[0], ''];
            }

            if ($count > 1) {
                // More than 1, add a message at the start that indicates how many.
                return ["{$count} Moodle tasks reported errors, maximum faildelay > {$faildelaymins} mins", implode("", $taskoutputs)];
            }

            // There are 0 tasks are failing, default to nothing.
            return ['', ''];
        };

        // Default to ok.
        $status = result::OK;
        $delay = 0;

        // Check if warn - if so then upgrade to warn.
        if ($maxdelaymins > $this->warnthreshold) {
            $status = result::WARNING;
            $delay = $this->warnthreshold;
        }

        // Check if error - if so then upgrade to error.
        if ($maxdelaymins > $this->errorthreshold) {
            $status = result::ERROR;
            $delay = $this->errorthreshold;
        }

        list($summary, $details) = $taskoutputfn($delay);

        return new result($status, nl2br($summary), nl2br($details));

    }
}
