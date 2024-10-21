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
 * Test class for tool_heartbeat\checker
 *
 * @package   tool_heartbeat
 * @author    Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright 2023, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checker_test extends \advanced_testcase {
    /**
     * Tests get_check_messages function
     */
    public function test_get_check_messages() {
        // Need to start output buffering, since get_check_messages closes it.
        ob_start();

        // Check API modifies DB state.
        $this->resetAfterTest(true);

        // Just test that the check API is working, and this returns some checks (for example the ones included with this plugin).
        $checks = checker::get_check_messages();
        $this->assertNotEmpty($checks);
    }

    /**
     * Provides values to determine_nagios_level test
     * @return array
     */
    public static function determine_nagios_level_provider(): array {
        return [
            'no messages' => [
                'levels' => [],
                'nagioslevel' => resultmessage::LEVEL_OK,
            ],
            'one OK message' => [
                'levels' => [resultmessage::LEVEL_OK],
                'nagioslevel' => resultmessage::LEVEL_OK,
            ],
            'one UNKNOWN message' => [
                'levels' => [resultmessage::LEVEL_UNKNOWN],
                'nagioslevel' => resultmessage::LEVEL_UNKNOWN,
            ],
            'one UNKNOWN and one OK' => [
                'levels' => [resultmessage::LEVEL_UNKNOWN, resultmessage::LEVEL_OK],
                'nagioslevel' => resultmessage::LEVEL_UNKNOWN,
            ],
            'one UNKNOWN and one WARNING' => [
                'levels' => [resultmessage::LEVEL_UNKNOWN, resultmessage::LEVEL_WARN],
                'nagioslevel' => resultmessage::LEVEL_WARN,
            ],
            'one UNKNOWN and on CRITICAL' => [
                'levels' => [resultmessage::LEVEL_UNKNOWN, resultmessage::LEVEL_CRITICAL],
                'nagioslevel' => resultmessage::LEVEL_CRITICAL,
            ],
        ];
    }

    /**
     * Tests determine_nagios_level function
     * @param array $levels
     * @param int $expectedlevel
     * @dataProvider determine_nagios_level_provider
     */
    public function test_determine_nagios_level(array $levels, int $expectedlevel) {
        // Generate a series of dummy messages with the given levels.
        $messages = array_map(function($level) {
            $msg = new resultmessage();
            $msg->level = $level;
            return $msg;
        }, $levels);

        // Confirm the correct level outputted.
        $level = checker::determine_nagios_level($messages);
        $this->assertEquals($expectedlevel, $level);
    }

    /**
     * Provides values to test_create_summary test
     * @return array
     */
    public static function create_summary_provider(): array {

        $warnmsg = new resultmessage();
        $warnmsg->level = resultmessage::LEVEL_WARN;
        $warnmsg->title = "test WARN title";

        $okmsg = new resultmessage();
        $okmsg->level = resultmessage::LEVEL_OK;
        $okmsg->title = "test OK title";

        $criticalmsg = new resultmessage();
        $criticalmsg->level = resultmessage::LEVEL_CRITICAL;
        $criticalmsg->title = "test CRITICAL title";

        // Pipes should be cleaned from output and replaced with [pipe].
        $criticalwithpipemsg = new resultmessage();
        $criticalwithpipemsg->level = resultmessage::LEVEL_CRITICAL;
        $criticalwithpipemsg->title = "test CRITICAL title |";

        return [
            'no messages (no message displayed)' => [
                'messages' => [],
                'expectedsummary' => "OK",
            ],
            'only OK (no message displayed)' => [
                'messages' => [$okmsg],
                'expectedsummary' => "OK",
            ],
            'only WARNING (shows error in top level)' => [
                'messages' => [$warnmsg],
                'expectedsummary' => $warnmsg->title,
            ],
            'mix of warning levels (shows summary of levels without including OK)' => [
                'messages' => [$warnmsg, $okmsg, $criticalmsg],
                'expectedsummary' => "Multiple problems detected: 1 WARNING, 1 CRITICAL",
            ],
            'pipe char in output is cleaned' => [
                'messages' => [$criticalwithpipemsg],
                'expectedsummary' => str_replace('|', '｜', $criticalwithpipemsg->title)
            ]
        ];
    }

    /**
     * Tests create_summary function
     * @param array $messages
     * @param string $expectedsummary
     * @dataProvider create_summary_provider
     */
    public function test_create_summary(array $messages, string $expectedsummary) {
        $summary = checker::create_summary($messages);
        $this->assertEquals($expectedsummary, $summary);
    }
}
