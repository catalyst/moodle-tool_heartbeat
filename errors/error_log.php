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
 * Tests all the different types of error logging
 *
 * @package    tool_heartbeat
 * @copyright  2026 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreStart
require(__DIR__ . '/../../../../config.php');
// @codingStandardsIgnoreEnd

require_login();
require_capability('moodle/site:config', context_system::instance());

$syscontext = context_system::instance();
$PAGE->set_url('/admin/tool/heartbeat/errors.php');
$PAGE->set_context($syscontext);
$PAGE->set_cacheable(false);
echo $OUTPUT->header();
echo $OUTPUT->heading("Error logs");

echo "<p>This will emit a whole bunch of errors to the error log in a couple ways.</p>";

// phpcs:disable moodle.PHP.ForbiddenFunctions.FoundWithAlternative
error_log('1/6 error using error_log()'); // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
error_log('2/6 error using error_log()'); // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
error_log('3/6 error using error_log(..., 4) to SAPI', 4); // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
error_log('4/6 error using error_log(..., 4) to SAPI', 4); // phpcs:ignore moodle.PHP.ForbiddenFunctions.FoundWithAlternative
// phpcs:enable moodle.PHP.ForbiddenFunctions.FoundWithAlternative
file_put_contents('php://stderr', "5/6 using file_put_contents to stderr\n", FILE_APPEND);
file_put_contents('php://stderr', "6/6 using file_put_contents to stderr\n", FILE_APPEND);

echo "<p>This should result is 6 unique lines in the error log</p>";
echo "<p>If you don't get 6, or if some lines are concatenated its not ideal.</p>";

echo $OUTPUT->footer();
