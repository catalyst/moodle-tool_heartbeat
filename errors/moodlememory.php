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
 * Tests all the different types of error classes
 *
 * @package    tool_heartbeat
 * @copyright  2020 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

// @codingStandardsIgnoreStart
require(__DIR__ . '/../../../../config.php');
// @codingStandardsIgnoreEnd

ini_set('memory_limit', '1k');

$max = 1000 * 1000 * 1000; // We should max out before 1 billion cycles.
$array = [];
for ($c = 0; $c < $max; $c++) {

    // A sleep(1) isn't actually counted so lets do some real work.
    $rand = random_bytes(100);
    $hash = substr(hash('sha256', $rand), 0, 10);

    $array[] = $hash;
    $memory = memory_get_usage();
    if ($c % 1000 == 0) {
        echo "Work $c of $max (memory = $memory)<br>";
    }
}

