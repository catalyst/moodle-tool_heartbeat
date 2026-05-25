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

use action_link;
use core\check\check;
use core\check\result;
use moodle_url;

/**
 * Scheduled task queue check.
 *
 * This alerts when scheduled tasks are overdue — i.e. their nextruntime has
 * passed but they have not yet been executed. This indicates cron is not
 * running, is running too slowly, or tasks are stuck.
 *
 * Thresholds can be tuned via $CFG->scheduledtaskagewarn (seconds, default 10 mins)
 * and $CFG->scheduledtaskageerror (seconds, default 1 hour).
 *
 * @package    tool_heartbeat
 * @copyright  2026 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduledqueue extends check {
    /**
     * Return result
     *
     * @return result
     */
    public function get_result(): result {
        $now = time();

        // Use the task manager API so that $CFG->scheduled_tasks config overrides
        // (which can force-disable or force-enable tasks) are respected.
        $alltasks = \core\task\manager::get_all_scheduled_tasks();

        $overdue = [];
        foreach ($alltasks as $task) {
            if (!$task->is_enabled()) {
                continue;
            }

            $age = $now - $task->get_next_run_time();
            if ($age > 0) {
                $overdue[] = (object)[
                    'classname' => get_class($task),
                    'age'       => $age,
                ];
            }
        }

        // Sort oldest first.
        usort($overdue, fn($a, $b) => $b->age <=> $a->age);

        $status = result::OK;
        $summary = get_string('scheduledqueueok', 'tool_heartbeat');
        $details = '';

        if (empty($overdue)) {
            return new result($status, $summary, $details);
        }

        $count = count($overdue);
        $oldest = reset($overdue);
        $maxage = (int) $oldest->age;

        $warnthreshold = (int) (get_config('tool_heartbeat', 'scheduledqueuewarn') ?: 10 * MINSECS);
        $errorthreshold = (int) (get_config('tool_heartbeat', 'scheduledqueueerror') ?: HOURSECS);

        if ($maxage > $warnthreshold) {
            $status = result::WARNING;
        }

        if ($maxage > $errorthreshold) {
            $status = result::ERROR;
        }

        if ($status === result::OK) {
            // Tasks are overdue but within the normal cron timing window — just informational.
            $summary = get_string('scheduledqueuepending', 'tool_heartbeat', $count);
            return new result(result::INFO, $summary, $details);
        }

        $summary = get_string('scheduledqueueoverdue', 'tool_heartbeat', [
            'count'     => $count,
            'age'       => format_time($maxage),
            'threshold' => '> ' . format_time($maxage > $errorthreshold ? $errorthreshold : $warnthreshold),
        ]);

        foreach ($overdue as $task) {
            $details .= get_string('scheduledqueuetaskdetail', 'tool_heartbeat', [
                'classname' => $task->classname,
                'age'       => format_time((int) $task->age),
            ]) . '<br>';
        }

        return new result($status, $summary, $details);
    }

    /**
     * Link to the scheduled tasks admin page.
     *
     * @return action_link|null
     */
    public function get_action_link(): ?action_link {
        return new action_link(
            new moodle_url('/admin/tool/task/scheduledtasks.php'),
            get_string('scheduledtasks', 'tool_task'),
        );
    }
}
