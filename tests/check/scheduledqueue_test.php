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

use core\check\result;
use tool_heartbeat\check\scheduledqueue;

/**
 * Unit tests for tool_heartbeat\check\scheduledqueue
 *
 * @package   tool_heartbeat
 * @copyright 2026 Brendan Heywood <brendan@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \tool_heartbeat\check\scheduledqueue
 */
final class scheduledqueue_test extends \advanced_testcase {
    /** A real scheduled task classname guaranteed to exist. */
    private const TASK = '\\core\\task\\session_cleanup_task';

    /** Another real core scheduled task for tests requiring multiple tasks. */
    private const SECOND_TASK = '\\core\\task\\backup_cleanup_task';

    /** A scheduled task belonging to a component disabled in PHPUnit by default. */
    private const DISABLED_COMPONENT_TASK = '\\logstore_standard\\task\\cleanup_task';

    /**
     * Set up: reset DB and push all scheduled tasks into the future so they
     * don't interfere with individual test cases.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        global $DB;
        // Move every enabled task's nextruntime into the future so tests
        // start from a clean "all on time" baseline.
        $DB->set_field_select('task_scheduled', 'nextruntime', time() + HOURSECS, 'disabled = 0');

        // Use tight thresholds (2 min warn, 5 min error) so tests don't need
        // to manipulate timestamps by large amounts.
        set_config('scheduledqueuewarn', 2 * MINSECS, 'tool_heartbeat');
        set_config('scheduledqueueerror', 5 * MINSECS, 'tool_heartbeat');
    }

    /**
     * Helper: set nextruntime for the test task relative to now.
     *
     * @param int $offsetsecs Offset in seconds from now.
     */
    private function set_nextruntime(int $offsetsecs): void {
        $this->set_task_nextruntime(self::TASK, $offsetsecs);
    }

    /**
     * Helper: set nextruntime for a task relative to now.
     *
     * @param string $classname The task classname.
     * @param int $offsetsecs Offset in seconds from now.
     */
    private function set_task_nextruntime(string $classname, int $offsetsecs): void {
        global $DB;
        $DB->set_field('task_scheduled', 'disabled', 0, ['classname' => $classname]);
        $DB->set_field('task_scheduled', 'nextruntime', time() + $offsetsecs, ['classname' => $classname]);
    }

    /**
     * When all tasks have a nextruntime in the future the check should be OK.
     */
    public function test_all_tasks_on_time(): void {
        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());
    }

    /**
     * A task that is overdue but within the warning threshold returns INFO.
     */
    public function test_overdue_within_warn_threshold_is_info(): void {
        // 1 minute overdue, warn threshold is 2 minutes.
        $this->set_nextruntime(-MINSECS);

        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::INFO, $result->get_status());
    }

    /**
     * A task overdue past the warning threshold but not the error threshold returns WARNING.
     */
    public function test_overdue_past_warn_threshold_is_warning(): void {
        // 3 minutes overdue — past warn (2 min) but under error (5 min).
        $this->set_nextruntime(-3 * MINSECS);

        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::WARNING, $result->get_status());
    }

    /**
     * A task overdue past the error threshold returns ERROR.
     */
    public function test_overdue_past_error_threshold_is_error(): void {
        // 10 minutes overdue — past error threshold (5 min).
        $this->set_nextruntime(-10 * MINSECS);

        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::ERROR, $result->get_status());
    }

    /**
     * Disabled tasks must be ignored, even if their nextruntime is long overdue.
     */
    public function test_disabled_task_is_ignored(): void {
        global $DB;

        // Push nextruntime way into the past and disable the task.
        $DB->set_field('task_scheduled', 'nextruntime', time() - DAYSECS, ['classname' => self::TASK]);
        $DB->set_field('task_scheduled', 'disabled', 1, ['classname' => self::TASK]);

        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());
    }

    /**
     * Tasks belonging to disabled components must be ignored.
     */
    public function test_disabled_component_task_is_ignored(): void {
        global $DB;

        // PHPUnit disables logstores by default, but set this explicitly so the
        // test covers the component-disabled behaviour.
        set_config('enabled_stores', '', 'tool_log');

        $DB->set_field('task_scheduled', 'nextruntime', time() - DAYSECS, ['classname' => self::DISABLED_COMPONENT_TASK]);
        $DB->set_field('task_scheduled', 'disabled', 0, ['classname' => self::DISABLED_COMPONENT_TASK]);

        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());
    }

    /**
     * The summary string mentions the count and the exceeded threshold, not the exact age.
     */
    public function test_summary_includes_count_and_threshold(): void {
        // Make two tasks overdue past the warning threshold (2 min).
        $this->set_task_nextruntime(self::TASK, -3 * MINSECS);
        $this->set_task_nextruntime(self::SECOND_TASK, -3 * MINSECS);

        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::WARNING, $result->get_status());

        $summary = $result->get_summary();
        $this->assertStringContainsString('2', $summary);
        $this->assertStringContainsString('>', $summary);
    }

    /**
     * When there are multiple overdue tasks, the details list each one.
     */
    public function test_details_lists_each_overdue_task(): void {
        $tasks = [self::TASK, self::SECOND_TASK];
        foreach ($tasks as $task) {
            $this->set_task_nextruntime($task, -3 * MINSECS);
        }

        $check = new scheduledqueue();
        $result = $check->get_result();

        $details = $result->get_details();
        foreach ($tasks as $task) {
            $this->assertStringContainsString(ltrim($task, '\\'), $details);
        }
    }

    /**
     * The most overdue task drives the status — a mix of INFO-level and
     * WARNING-level overdue tasks should still produce a WARNING overall.
     */
    public function test_worst_task_drives_status(): void {
        // First task: just past warn threshold.
        $this->set_task_nextruntime(self::TASK, -3 * MINSECS);
        // Second task: within warn threshold (INFO level only).
        $this->set_task_nextruntime(self::SECOND_TASK, -MINSECS);

        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::WARNING, $result->get_status());
    }

    /**
     * Verify thresholds are read from plugin config, not hardcoded.
     */
    public function test_custom_thresholds_are_respected(): void {
        // Set a very tight warn threshold (10 seconds).
        set_config('scheduledqueuewarn', 10, 'tool_heartbeat');
        set_config('scheduledqueueerror', 30, 'tool_heartbeat');

        // 15 seconds overdue — past warn (10s) but under error (30s).
        $this->set_nextruntime(-15);

        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::WARNING, $result->get_status());

        // 60 seconds overdue — past error (30s).
        $this->set_nextruntime(-60);
        $result = $check->get_result();
        $this->assertEquals(result::ERROR, $result->get_status());
    }

    /**
     * A task disabled via $CFG->scheduled_tasks must be ignored even if its
     * DB record has disabled = 0 and nextruntime is long overdue.
     */
    public function test_config_disabled_task_is_ignored(): void {
        global $CFG, $DB;

        $DB->set_field('task_scheduled', 'nextruntime', time() - DAYSECS, ['classname' => self::TASK]);
        $DB->set_field('task_scheduled', 'disabled', 0, ['classname' => self::TASK]);

        // Force-disable via config.php override.
        $CFG->scheduled_tasks = [
            self::TASK => ['disabled' => 1],
        ];

        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());
    }

    /**
     * A task disabled in the DB but force-enabled via $CFG->scheduled_tasks
     * must be included in the overdue check.
     */
    public function test_config_enabled_task_is_included(): void {
        global $CFG, $DB;

        // Disable and make overdue in DB.
        $DB->set_field('task_scheduled', 'disabled', 1, ['classname' => self::TASK]);
        $DB->set_field('task_scheduled', 'nextruntime', time() - 10 * MINSECS, ['classname' => self::TASK]);

        // Without override — should be OK because the task is DB-disabled.
        $check = new scheduledqueue();
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());

        // Force-enable via config.php override.
        $CFG->scheduled_tasks = [
            self::TASK => ['disabled' => 0],
        ];

        $result = $check->get_result();
        $this->assertEquals(result::ERROR, $result->get_status());
    }
}
