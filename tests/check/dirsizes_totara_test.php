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

use tool_heartbeat\check\dirsizes;

/**
 * Test class for Totara tool_heartbeat\check\dirsizes
 *
 * @package   tool_heartbeat
 * @author    Alex Damsted <alexdamsted@catalyst-au.net>
 * @copyright 2025, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \tool_heartbeat\check\dirsizes
 */
final class dirsizes_totara_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Ensure get_directory_size_totara counts file sizes correctly.
     *
     * @covers ::get_directory_size_totara
     */
    public function test_dirsize_totara_counts_files(): void {
        global $CFG;
        $dir = make_request_directory('tool_heartbeat_test1');
        $CFG->tempdir = $dir;

        // Create two test files with known sizes.
        file_put_contents($dir . '/file1.txt', str_repeat('a', 100));
        file_put_contents($dir . '/file2.txt', str_repeat('b', 200));
        $check = new dirsizes();
        $size = $this->invokeMethod($check, 'get_directory_size_totara', [$CFG->tempdir]);

        $this->assertEquals(300, $size);
    }

    /**
     * Ensure get_directory_size_totara counts nested files in subdirectories.
     *
     * @covers ::get_directory_size_totara
     */
    public function test_dirsize_totara_counts_nested_files(): void {
        global $CFG;

        $dir = make_request_directory('tool_heartbeat_test2');
        $CFG->tempdir = $dir;

        // Create subdirectory with file.
        $subdir = $dir . '/subdir';
        check_dir_exists($subdir);
        file_put_contents($subdir . '/nested.txt', str_repeat('x', 150));

        $check = new dirsizes();
        $size = $this->invokeMethod($check, 'get_directory_size_totara', [$CFG->tempdir]);

        $this->assertGreaterThanOrEqual(150, $size);
    }

    /**
     * Call private methods.
     *
     * @param object $object
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected function invokemethod($object, string $method, array $args = []) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}

