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
 * <insertdescription>
 *
 * @package   tool_heartbeat
 * @author    Jason den Dulk <jasondendulk@catalyst-au.net>
 * @copyright 2022, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class lib_test extends advanced_testcase {
    public function test_get_allowed_ips() {
        set_config('allowedips', '127.0.0.1');
        set_config('allowedips_forced', '127.0.0.2');
        $this->assertEquals("127.0.0.1\n127.0.0.2", tool_heartbeat\lib::get_allowed_ips());

        set_config('allowedips', '127.0.0.1');
        set_config('allowedips_forced', '');
        $this->assertEquals("127.0.0.1", tool_heartbeat\lib::get_allowed_ips());

        set_config('allowedips', '');
        set_config('allowedips_forced', '127.0.0.2');
        $this->assertEquals("127.0.0.2", tool_heartbeat\lib::get_allowed_ips());

        set_config('allowedips', '');
        set_config('allowedips_forced', '');
        $this->assertEquals('', tool_heartbeat\lib::get_allowed_ips());
    }
}
