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
 * Very basic host-is-up check.
 *
 * This check avoids any dependency on external services. It is especially
 * useful for kubernetes liveness checks, hence the name.
 *
 * Use /admin/tool/heartbeat if you want to ensure Moodle is actually running.
 *
 * @package    tool_heartbeat
 * @copyright  2019 Garth Williamson <garth@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// @codingStandardsIgnoreFile
echo("php is ALIVE");
