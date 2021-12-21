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
 * Tests for logs
 *
 * @package     tool_heartbeat
 * @author      Srdjan Janković <srdjan@catalyst.net.nz>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_heartbeat\wrapper;

use base_testcase;

use tool_heartbeat\logger;

defined('MOODLE_INTERNAL') || die();

class syslog_test extends base_testcase {
    public function test_syslog() {
        logger::log_to_stream('syslog://local0/TEST', ['test']);
        $this->assertTrue(true);
    }

    public function test_syslog_facility() {
        $this->assertEquals(LOG_USER, syslog::syslog_facility('user'));
        $this->assertEquals(LOG_LOCAL0, syslog::syslog_facility('local0'));
        $this->assertEquals(LOG_LOCAL3, syslog::syslog_facility('local3'));
        $this->assertEquals(LOG_LOCAL7, syslog::syslog_facility('local7'));
    }
}
