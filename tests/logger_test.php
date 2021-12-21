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

namespace tool_heartbeat;

use base_testcase;

defined('MOODLE_INTERNAL') || die();

class logger_test extends base_testcase {
    protected static $stats = ['Test1', 'Test2'];

    public static function stats() {
        return self::$stats;
    }

    public function test_log() {
        GLOBAL $PAGE;

        $protocol = 'test-heartbeat-log';
        $stream = "$protocol://teststream";
        if (!stream_wrapper_register($protocol, __NAMESPACE__.'\list_stream')) {
            throw new \Exception("Failed to register $protocol protocol");
        }

        logger::register_stat_callback([__NAMESPACE__.'\logger_test', 'stats'], $stream);

        $page = 'test';
        $PAGE->set_url("/$page");
        logger::log();

        if ($fh = fopen($stream, 'r')) {
            $this->assertEquals(
                implode("\n", array_map(function ($l) use ($page) { return "$page - $l"; }, self::$stats)),
                fread($fh, 4096)
            );
        } else {
            $this->fail("Cannot open $stream for reading");
        }
    }

    public function test_default_stats() {
        $cnt = function_exists('memory_get_peak_usage') ? 3 : 2;

        $stats = logger::default_stats();
        $this->assertEquals($cnt, count($stats));
    }
}

class list_stream {
    private $varname;

    function stream_open($path, $mode, $options, &$opened_path) {
        $url = parse_url($path);
        $this->varname = $url["host"];

        if (!isset($GLOBALS[$this->varname])) {
            $GLOBALS[$this->varname] = [];
        }

        return true;
    }

    function stream_read($count) {
        return implode("\n", $GLOBALS[$this->varname]);
    }

    function stream_write($data) {
        $GLOBALS[$this->varname][] = $data;
        $this->position = count($GLOBALS[$this->varname]) - 1;
        return strlen($data);
    }

    function stream_eof() {
        return false;
    }
}
