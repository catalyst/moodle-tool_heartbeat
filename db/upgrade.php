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
 * DB upgrade script.
 *
 * @package    tool_heartbeat
 * @author     Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright  Catalyst IT 2023
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade
 * @param int $oldversion
 */
function xmldb_tool_heartbeat_upgrade($oldversion) {
    if ($oldversion < 2023102400) {
        // If there are issues with split caches they need to be exposed
        // after some time for them to diverge.
        if (class_exists('\core\check\manager')) {
            \tool_heartbeat\check\cachecheck::ping('web');
            \tool_heartbeat\check\cachecheck::ping('cron');
        }
        upgrade_plugin_savepoint(true, 2023102400, 'tool', 'heartbeat');
    }

    return true;
}
