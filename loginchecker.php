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
 * Failed Login Checker
 *
 * @package    tool_heartbeat
 * @copyright  2019 Paul Damiani <pauldamiani@catalyst-au.net>
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

defined('MOODLE_INTERNAL');

// We want this to run regardless if there are any pending upgrades.
define('NO_UPGRADE_CHECK', true);

$options = array(
    'help' => false,
    'critthresh' => 500,
    'warnthresh' => 10,
    'logtime' => 5,
);

if (isset($argv)) {
    // If run from the CLI.
    define('CLI_SCRIPT', true);
    require_once(__DIR__ . '/../../../config.php');
    require_once('nagios.php');
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
        print "Checks the moodle database for failed login attempts in a given time frame

        loginchecker.php [options]

        Options:
        -h, --help            Print out this help
            --critthresh=n    Threshold for number of failed logins to trigger a critical error (default 500 attempts)
            --warnthresh=n    Threshold for number of failed logins to trigger a warning (default 10 attempts)
            --logtime=n       Time in minutes to check back for a critical error (default 5 minutes prior)

        Example:
        \$sudo -u www-data /usr/bin/php admin/tool/heartbeat/loginchecker.php --logtime=60\n";

        die;
    }

} else {
    // If run from the web.
    require_once(__DIR__ . '/../../../config.php');
    require_once('nagios.php');

    $options['critthresh'] = optional_param('critthresh', 500, PARAM_INT);
    $options['warnthresh'] = optional_param('warnthresh', 10, PARAM_INT);
    $options['logtime'] = optional_param('logtime', 5, PARAM_INT);

    header("Content-Type: text/plain");

    // Make sure varnish doesn't cache this. But it still might so go check it!
    header('Pragma: no-cache');
    header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
    header('Expires: Tue, 04 Sep 2012 05:32:29 GMT');
}

global $DB;

$checktime = time() - ($options['logtime'] * 60);

$sqlstring = "SELECT count(*) AS logincount
                FROM {logstore_standard_log}
               WHERE target = 'user_login'
                 AND timecreated > :checktime";

$tablequery = $DB->get_record_sql($sqlstring, array('checktime' => $checktime));

$count = $tablequery->logincount;

if ($count > $options['critthresh']) {
    send_critical("$count failed logins in the last ". $options['logtime'] ." minute(s).");
} else if ($count > $options['warnthresh']) {
    send_warning("$count Failed logins in the last ". $options['logtime'] ." minute(s).");
} else {
    send_good("Normal Login behaivour\n");
}
