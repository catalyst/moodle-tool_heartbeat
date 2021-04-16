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
 * This script allows you to mute the adminlogin check time.
 *
 * @package    tool_heartbeat
 * @copyright  2021 Brendan Heywood (brendan@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'mute' => false,
], [
    'h' => 'help',
    'm' => 'mute',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !$options['mute']) {
    $help = <<<EOT
Mutes the admin check alert on recent logins

If triggered this script stores a timestamp of now and any logins prior to
this will be ignored. Any new logins or action after will alert again.

Options:
-h, --help          Print out this help
-m, --mute          Mute the check prior to now

Example:
\$ sudo -u www-data php admin/tool/heartbeat/cli/muteadminlogin.php --mute

EOT;

    echo $help;
    die;
}

$now = time();
$show = userdate($now);

set_config('adminmute', $now, 'tool_heartbeat');
echo "Login check muted for times prior to $show\n";

exit(0); // 0 means success.
