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
 * Login Checker for the super user
 *
 * @package    tool_heartbeat
 * @copyright  2020 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This can be run either as a web api, or on the CLI. When run on the
 * CLI it conforms to the Nagios plugin standard.
 *
 * See also:
 *  - http://nagios.sourceforge.net/docs/3_0/pluginapi.html
 *  - https://nagios-plugins.org/doc/guidelines.html#PLUGOUTPUT
 *
 */

// @codingStandardsIgnoreStart
defined('MOODLE_INTERNAL');

// We want this to run regardless if there are any pending upgrades.
define('NO_UPGRADE_CHECK', true);
// @codingStandardsIgnoreEnd

$options = array(
    'help' => false,
    'critthresh' => 60 * 60 * 24 * 3,
    'warnthresh' => 60 * 60 * 24 * 7,
);

if (isset($argv)) {
    // If run from the CLI.
    define('CLI_SCRIPT', true);
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__.'/nagios.php');
    require_once($CFG->libdir . '/clilib.php');

    list($options, $unrecognized) = cli_get_params($options,
    array(
        'h' => 'help',
        )
    );

    if ($unrecognized) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }

    if ($options['help']) {
        print "Checks the moodle database for a recent login by the super user account

        loginchecker.php [options]

        Options:
        -h, --help            Print out this help
            --critthresh=n    Threshold in seconds for a recent login to trigger a critical error (default 3 days)
            --warnthresh=n    Threshold in seconds for a recent login to trigger a warning (default 7 days)

        Example:
        \$sudo -u www-data /usr/bin/php admin/tool/heartbeat/adminloginchecker.php\n";

        die;
    }

} else {
    // If run from the web.
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__.'/nagios.php');
    tool_heartbeat\lib::validate_ip_against_config();

    $options['critthresh'] = optional_param('critthresh', $options['critthresh'],  PARAM_INT);
    $options['warnthresh'] = optional_param('warnthresh', $options['warnthresh'], PARAM_INT);

    header("Content-Type: text/plain");

    // Make sure varnish doesn't cache this. But it still might so go check it!
    header('Pragma: no-cache');
    header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
    header('Expires: Tue, 04 Sep 2012 05:32:29 GMT');
}

$admin = get_admin();
$latest = max($admin->lastaccess, $admin->lastlogin);
$recency = time() - $latest;
$delta = format_time($recency);

$mutedbefore = get_config('tool_heartbeat', 'adminmute');
$muteddelta = '';
if ($mutedbefore && ($mutedbefore > $latest)) {
    $muteddelta = format_time($mutedbefore - time());
}

$muteinfo = "\nThis can be muted via php admin/tool/heartbeat/cli/muteadminlogin.php --mute";

if ($muteddelta && ($recency < $options['critthresh'] || $recency < $options['critthresh'])) {
    send_good("Last admin login was $delta ago but MUTED $muteddelta ago");
} else if ($recency < $options['critthresh']) {
    send_critical("Last admin login was $delta ago < " . format_time($options['critthresh']), $muteinfo);
} else if ($recency < $options['warnthresh']) {
    send_warning("Last admin login was $delta ago < " . format_time($options['warnthresh']), $muteinfo);
} else {
    send_good("No recent main admin login\n");
}
