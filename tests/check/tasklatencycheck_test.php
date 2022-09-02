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

/**
 * Test class for tool_heartbeat\task\tasklatencycheck
 *
 * @package   tool_heartbeat
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright 2022, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tasklatencycheck_test extends \advanced_testcase {

    /**
     * Test check for tasks start time delay.
     *
     * @covers \tool_heartbeat\task\tasklatencycheck::get_result
     */
    public function test_start_time_drift() {
        if (!class_exists('core\check\check')) {
            $this->markTestSkipped();
        }
        global $CFG, $DB;
        $this->resetAfterTest(true);
        // Set only the starttime drift latency field.
        set_config('tasklatencymonitoring', '\logstore_standard\task\cleanup_task, 0, 5, 0', 'tool_heartbeat');
        $dbman = $DB->get_manager();
        $lockstats = $dbman->table_exists(new \xmldb_table('tool_lockstats_history'));
        if ($lockstats) {
            $CFG->lock_factory = \tool_lockstats\proxy_lock_factory::class;
        }

        $DB->set_field('task_scheduled', 'nextruntime', time() + 5 * MINSECS,
            ['classname' => '\\logstore_standard\\task\\cleanup_task']);

        // Task is set to run in 5 mins, no issues here.
        $check = new \tool_heartbeat\check\tasklatencycheck();
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());

        // Now lets test it in the, but within the window.
        $DB->set_field('task_scheduled', 'nextruntime', time() + 3 * MINSECS,
            ['classname' => '\\logstore_standard\\task\\cleanup_task']);
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());

        // Now past the window.
        $DB->set_field('task_scheduled', 'nextruntime', time() - 6 * MINSECS,
            ['classname' => '\\logstore_standard\\task\\cleanup_task']);
        $result = $check->get_result();
        $this->assertEquals(result::CRITICAL, $result->get_status());

        if ($lockstats) {
            // Now set a lock on the task. This should prevent critical.
            $CFG->lock_factory = \tool_lockstats\proxy_lock_factory::class;
            $DB->insert_record('tool_lockstats_locks', [
                'resourcekey' => '\\logstore_standard\\task\\cleanup_task'
            ]);
            $result = $check->get_result();
            $this->assertEquals(result::OK, $result->get_status());
        }
    }

    /**
     * Test check for tasks not running.
     *
     * @covers \tool_heartbeat\task\tasklatencycheck::get_result
     */
    public function test_task_not_run() {
        if (!class_exists('core\check\check')) {
            $this->markTestSkipped();
        }
        global $CFG, $DB;
        $this->resetAfterTest(true);
        // Set only the last run latency field.
        set_config('tasklatencymonitoring', '\logstore_standard\task\cleanup_task, 0, 0, 5', 'tool_heartbeat');
        $dbman = $DB->get_manager();
        $lockstats = $dbman->table_exists(new \xmldb_table('tool_lockstats_history'));
        if ($lockstats) {
            $CFG->lock_factory = \tool_lockstats\proxy_lock_factory::class;
        }

        // Last run 1 minute ago, within window.
        $DB->set_field('task_scheduled', 'lastruntime', time() - 1 * MINSECS,
            ['classname' => '\\logstore_standard\\task\\cleanup_task']);
        $check = new \tool_heartbeat\check\tasklatencycheck();
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());

        // In the future? This should never happen, but shouldn't make the check barf.
        $DB->set_field('task_scheduled', 'lastruntime', time() + 5 * MINSECS,
            ['classname' => '\\logstore_standard\\task\\cleanup_task']);
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());

        // Now outside of the delay latency.
        $DB->set_field('task_scheduled', 'lastruntime', time() - 10 * MINSECS,
            ['classname' => '\\logstore_standard\\task\\cleanup_task']);
        $result = $check->get_result();
        $this->assertEquals(result::CRITICAL, $result->get_status());

        if ($lockstats) {
            // Now set a lock on the task. This should prevent critical.
            $CFG->lock_factory = \tool_lockstats\proxy_lock_factory::class;
            $DB->insert_record('tool_lockstats_locks', [
                'resourcekey' => '\\logstore_standard\\task\\cleanup_task'
            ]);
            $result = $check->get_result();
            $this->assertEquals(result::OK, $result->get_status());
        }
    }

    /**
     * Test check for task duration.
     *
     * @covers \tool_heartbeat\task\tasklatencycheck::get_result
     */
    public function test_task_run_duration() {
        if (!class_exists('core\check\check')) {
            $this->markTestSkipped();
        }
        global $CFG, $DB;
        $this->resetAfterTest(true);
        // Set only the last run latency field.
        set_config('tasklatencymonitoring', '\logstore_standard\task\cleanup_task, 5, 0, 0', 'tool_heartbeat');
        $dbman = $DB->get_manager();
        $lockstats = $dbman->table_exists(new \xmldb_table('tool_lockstats_history'));
        if ($lockstats) {
            $CFG->lock_factory = \tool_lockstats\proxy_lock_factory::class;
        }

        // Now we should check the duration of the last run, whenever it was.
        $logs = $dbman->table_exists(new \xmldb_table('task_log'));
        if (!$logs && !$lockstats) {
            // Neither tracking method exists for us to test against. Just skip.
            $this->markTestSkipped();
        }

        $logrecord = [
            'type' => 0,
            'component' => 'logstore_standard',
            'classname' => '\\logstore_standard\\task\\cleanup_task',
            'userid' => 1,
            'dbreads' => 10,
            'dbwrites' => 10,
            'result' => 0,
            'output' => 'testdata',
        ];
        $lockstatsrecord = [
            'taskid' => 1,
            'component' => 'logstore_standard',
            'classname' => '\\logstore_standard\\task\\cleanup_task',
        ];

        // Test a good duration.
        // We need to set different field based on whether lockstats or logs for all these tests.
        if ($logs) {
            $logrecord['timestart'] = time() - 15 * MINSECS;
            $logrecord['timeend'] = time() - 12 * MINSECS;
            $DB->insert_record('task_log', $logrecord);

        } else {
            $lockstatsrecord['duration'] = 180;
            $DB->insert_record('tool_lockstats_history', $lockstatsrecord);
        }

        $check = new \tool_heartbeat\check\tasklatencycheck();
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());

        // Now test a negative duration. Can never happen, but the check shouldn't barf.
        if ($logs) {
            $logrecord['timestart'] = time() - 12 * MINSECS;
            $logrecord['timeend'] = time() - 15 * MINSECS;
            $DB->delete_records('task_log');
            $DB->insert_record('task_log', $logrecord);

        } else {
            $lockstatsrecord['duration'] = -180;
            $DB->delete_records('tool_lockstats_history');
            $DB->insert_record('tool_lockstats_history', $lockstatsrecord);
        }

        $check = new \tool_heartbeat\check\tasklatencycheck();
        $result = $check->get_result();
        $this->assertEquals(result::OK, $result->get_status());

        // Now test a duration longer than the configured duration.
        if ($logs) {
            $logrecord['timestart'] = time() - 15 * MINSECS;
            $logrecord['timeend'] = time() - 5 * MINSECS;
            $DB->delete_records('task_log');
            $DB->insert_record('task_log', $logrecord);

        } else {
            $lockstatsrecord['duration'] = 600;
            $DB->delete_records('tool_lockstats_history');
            $DB->insert_record('tool_lockstats_history', $lockstatsrecord);
        }

        $check = new \tool_heartbeat\check\tasklatencycheck();
        $result = $check->get_result();
        $this->assertEquals(result::CRITICAL, $result->get_status());
    }
}
