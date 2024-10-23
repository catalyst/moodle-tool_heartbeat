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

    /** @var \stdClass $task Record of task that is failing **/
    private $task;

    /** @var \stdClass $config Configuration of task alerting defaults */
    private $config;

    /**
     * Constructor
     */
    public function __construct($task = null, $config = null) {
        $this->task = $task;
        $this->config = $config;

    }

    /**
     * A link to check task logs
     *
     * @return \action_link|null
     */
    public function get_action_link(): ?\action_link {
        $url = new \moodle_url('/admin/tasklogs.php');
        return new \action_link($url, get_string('tasklogs', 'tool_task'));
    }

    /**
     * Return result
     * @return result
     */
    public function get_result() : result {
        global $DB;

        // Return OK if no task errors.
        if (!isset($this->task)) {
            $count = $DB->count_records_sql("SELECT COUNT(*) FROM {task_scheduled} WHERE faildelay = 0 AND disabled = 0");
            return new result(result::OK, get_string('checkfailingtaskok', 'tool_heartbeat', $count), '');
        }

        // Find the largest faildelay out of both adhoc and scheduled tasks.
        $maxdelaymins = !empty($this->task->faildelay) ? $this->task->faildelay / 60 : 0;

        // Default to ok.
        $status = result::OK;

        // Check if warn - if so then upgrade to warn.
        if ($maxdelaymins > $this->warnthreshold) {
            $status = result::WARNING;
        }

        // Check if error - if so then upgrade to error.
        if ($maxdelaymins > $this->errorthreshold) {
            $status = result::ERROR;
        }

        // Cap the status to the maximum allowed by configuration.
        $status = $this->get_highest_allowed_warning_level($status);

        return new result($status, $this->task->message, '');
    }

    /**
     * Returns each moodle core result error status as a map of string => integer value, this is used for sorting
     * alerts when determining the maximum allowed level.
     *
     * @return array{na: int, ok: int, info: int, unknown: int, warning: int, error: int, critical: int}
     */
    public static function get_integer_values_array() {
        return array(
            result::NA => 0,
            result::OK => 1,
            result::INFO => 2,
            result::UNKNOWN => 3,
            result::WARNING => 4,
            result::ERROR => 5,
            result::CRITICAL => 6,
        );
    }

    /**
     * Look at the task warning configuration and apply the global default, or if a specific task
     * default is supplied in the configuration, use that, and then cap the status to either the passed
     * in real status, or the maximum permitted in config if the real status exceeds it.
     * @param string $status
     * @return string Allowed status
     */
    public function get_highest_allowed_warning_level($status) {
        // No configuration exists, short circuit.
        if (!isset($this->config)) {
            return $status;
        }
        // Before any configuration tests, the default max allowed is the same as the status reported.
        $max = $status;
        // If there's a global task default, apply that first.
        if (isset($this->config['*']) && isset($this->config['*']['maxfaildelaylevel'])) {
            $max = $this->config['*']['maxfaildelaylevel'];
        }
        // Now look for specific config for the task classname, this takes precedence.
        if (isset($this->task) &&
            isset($this->config[$this->task->classname])
            && isset($this->config[$this->task->classname]['maxfaildelaylevel'])) {
            $max = $this->config[$this->task->classname]['maxfaildelaylevel'];
        }
        // Get a map of result string to integers representing their "order level".
        $map = self::get_integer_values_array();
        // Get the order value of each status.
        $maxint = $map[$max];
        $realint = $map[$status];
        // Determine the lowest ordered status of the two.
        $finalint = min($maxint, $realint);
        // Flip the array to be integer => string constant and return the allowed final status.
        return array_flip($map)[$finalint];

    }

    /**
     * Get the short check name
     *
     * @return string
     */
    public function get_name(): string {
        $name = parent::get_name();
        if (!isset($this->task)) {
            return $name;
        }
        return get_string('checkfailingtaskchecktask', 'tool_heartbeat', $this->task->classname);
    }

    /**
     * Get the check reference.
     * If this check is on a specific task, use the task classname.
     *
     * @return string must be globally unique
     */
    public function get_ref(): string {
        if (!isset($this->task)) {
            return parent::get_ref();
        }
        // Format nicely to use as a query param.
        return trim(str_replace('\\', '_', $this->task->classname), '_');
    }



    /**
     * Gets an array of all failing tasks, stored as \stdClass.
     *
     * @return array of failing tasks
     */
    public static function get_failing_tasks(): array {
        GLOBAL $DB, $CFG;
        $tasks = [];

        // Instead of using task API here, we read directly from the database.
        // This stops errors originating from broken tasks.
        $scheduledtasks = $DB->get_records_sql("SELECT * FROM {task_scheduled} WHERE faildelay > 0 AND disabled = 0");

        foreach ($scheduledtasks as $task) {
            $task->message = "SCHEDULED TASK: {$task->classname} Delay: {$task->faildelay}\n";
            $tasks[] = new \tool_heartbeat\check\failingtaskcheck($task, $CFG->tool_heartbeat_tasks);
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
            $record->message = "ADHOC TASK: {$record->classname} Delay: {$record->faildelay} {$duplicatemsg}\n";
            $tasks[] = new \tool_heartbeat\check\failingtaskcheck($record, $CFG->tool_heartbeat_tasks);
        }
        return $tasks;
    }
}
