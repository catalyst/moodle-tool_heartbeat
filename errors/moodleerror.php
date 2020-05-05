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

// @codingStandardsIgnoreStart
require(__DIR__ . '/../../../../config.php');
// @codingStandardsIgnoreEnd

$code = required_param('code', PARAM_INT);

header("HTTP/1.0 $code Moodle custom error");

$syscontext = context_system::instance();
$PAGE->set_url('/admin/tool/heartbeat/errors.php');
$PAGE->set_context($syscontext);
$PAGE->set_cacheable(false);
echo $OUTPUT->header();
echo $OUTPUT->heading("Error $code");

echo "This is a $code error page with full styling";

echo $OUTPUT->footer();
