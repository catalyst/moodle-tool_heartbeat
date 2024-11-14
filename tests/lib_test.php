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
 * Test class for tool_heartbeat\lib
 *
 * @package   tool_heartbeat
 * @author    Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2022, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {
    /**
     * Test lib::test_get_allowed_ips()
     */
    public function test_get_allowed_ips() {
        $this->resetAfterTest();

        set_config('allowedips', '127.0.0.1', 'tool_heartbeat');
        set_config('allowedips_forced', '127.0.0.2', 'tool_heartbeat');
        $this->assertEquals('127.0.0.1' . PHP_EOL . '127.0.0.2', lib::get_allowed_ips());

        set_config('allowedips', '127.0.0.1', 'tool_heartbeat');
        set_config('allowedips_forced', '', 'tool_heartbeat');
        $this->assertEquals("127.0.0.1", lib::get_allowed_ips());

        set_config('allowedips', '', 'tool_heartbeat');
        set_config('allowedips_forced', '127.0.0.2', 'tool_heartbeat');
        $this->assertEquals("127.0.0.2", lib::get_allowed_ips());

        set_config('allowedips', '', 'tool_heartbeat');
        set_config('allowedips_forced', '', 'tool_heartbeat');
        $this->assertEquals('', lib::get_allowed_ips());
    }

    /**
     * Provides values to test error log ping.
     * @return array
     */
    public function process_error_log_ping_provider(): array {
        return [
            'no period set - disabled' => [
                'errorloglastpinged' => null,
                'errorlog' => null,
                'expectedtimebefore' => null,
            ],
            'only period set' => [
                'errorloglastpinged' => null,
                'errorlog' => 1 * MINSECS,
                // Update to latest time.
                'expectedtimebefore' => time() + 10,
            ],
            'period has passed, time should change' => [
                'errorloglastpinged' => 1,
                'errorlog' => 1 * MINSECS,
                // Update to latest time.
                'expectedtimebefore' => time() + 10,
            ],
            'period not passed yet, time unchanged' => [
                'errorloglastpinged' => time(),
                'errorlog' => 1 * MINSECS,
                // Remain unchanged, i.e. exactly equal.
                'expectedtimebefore' => time(),
            ],
        ];
    }

    /**
     * Tests process_error_log_ping function
     *
     * @param int|null $errorloglastpinged next error value to set
     * @param int|null $errorlog error log value to set
     * @param bool $expectrun
     * @dataProvider process_error_log_ping_provider
     */
    public function test_process_error_log_ping(?int $errorloglastpinged, ?int $errorlog, ?int $expectedtimebefore) {
        $this->resetAfterTest(true);
        set_config('errorloglastpinged', $errorloglastpinged, 'tool_heartbeat');
        set_config('errorlog', $errorlog, 'tool_heartbeat');
        lib::process_error_log_ping();

        $valueafter = get_config('tool_heartbeat', 'errorloglastpinged');

        // Assert the time was not set at all.
        if (is_null($expectedtimebefore)) {
            $this->assertFalse($valueafter);
        } else {
            // New value should have set current time as the last pinged time.
            // We use less than and add some buffer in the test cases to account
            // for tests that might happen over a few seconds.
            $this->assertLessThanOrEqual($expectedtimebefore, $valueafter);
        }
    }
}
