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
 * Performs a progress bar test
 *
 * @package    tool_heartbeat
 * @copyright  2017 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_OUTPUT_BUFFERING', true); // Progress bar is used here.

// @codingStandardsIgnoreStart
// Ignore expected config.php inclusion for $argv as CLI_SCRIPT must be defined first.
if (isset($argv)) {
    define('CLI_SCRIPT', true);
}
// Ignore expected login check.
require(__DIR__ . '/../../../config.php');
// @codingStandardsIgnoreEnd

tool_heartbeat\lib::validate_ip_against_config();

$syscontext = context_system::instance();
$PAGE->set_url('/admin/tool/heartbeat/progress.php');
$PAGE->set_context($syscontext);
$PAGE->set_cacheable(false);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('progress', 'tool_heartbeat'));
echo html_writer::tag('p', get_string('progresshelp', 'tool_heartbeat'));
$progressbar = new progress_bar();
$progressbar->create();
echo $OUTPUT->footer();

// Total should be 10 seconds. This has been tuned a few times and the
// story here is there is an intermittent slowness somewhere in the stack
// which means that a small percentage of checks have a long TTFB and
// so icinga / gocd checks fail. This is likely to be an issue in the
// stack and not the test, so we have just added more margin here.
$total = 10;
$progressbar->update_full(0, '0%');
for ($c = 1; $c <= 100; $c += .3) {
    usleep($total * 1000);
    $progressbar->update_full(sprintf('%.1f', $c), sprintf("You are up to %.1f out of 100", $c));
}
$progressbar->update_full($c, sprintf("You are up to %.1f out of 100", $c));

