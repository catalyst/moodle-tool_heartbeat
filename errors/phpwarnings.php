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
 * @copyright  2024 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

// @codingStandardsIgnoreStart
require(__DIR__ . '/../../../../config.php');
// @codingStandardsIgnoreEnd

// So we don't brick our session.
\core\session\manager::write_close();

echo "This is an ok page which emits notices and warnings, and error logs";

trigger_error("This is a notice", E_USER_NOTICE);
trigger_error("This is a warning", E_USER_WARNING);

// @codingStandardsIgnoreStart
error_log("This is an error_log");
// @codingStandardsIgnoreEnd
file_put_contents("php://stderr", "This writing to php://stderr");

