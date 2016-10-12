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
 * CRON health check
 *
 * @package    tool_heartbeat
 * @copyright  2015 Brendan Heywood <brendan@catalyst-au.net>
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

define('NO_UPGRADE_CHECK', true);

$cronthreshold  = 6;   // Hours.
$cronwarn       = 2;   // Hours.
$delaythreshold = 600; // Minutes.
$delaywarn      = 60;  // Minutes.

$dirroot = '../../../';


if (isset($argv)) {
    // If run from the CLI.
    define('CLI_SCRIPT', true);

    $last = $argv[count($argv) - 1];
    if ($last && is_dir($last) ) {
        $dirroot = array_pop($argv).'/';
        array_pop($_SERVER['argv']);
    }


    require($dirroot.'config.php');
    require_once($CFG->libdir.'/clilib.php');

    list($options, $unrecognized) = cli_get_params(
        array(
            'help' => false,
            'cronwarn'   => $cronthreshold,
            'cronerror'  => $cronwarn,
            'delaywarn'  => $delaythreshold,
            'delayerror' => $delaywarn
        ),
        array(
            'h'   => 'help'
        )
    );

    if ($unrecognized) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }

    if ($options['help']) {
        print "Check the moodle cron system for when it last ran and any task fail delays

croncheck.php [options] [moodle path]

Options:
-h, --help          Print out this help
    --cronwarn=n    Threshold for no cron run error in hours (default $cronthreshold)
    --cronerror=n   Threshold for no cron run warn in hours (default $cronwarn)
    --delaywarn=n   Threshold for fail delay cron error in minutes (default $delaythreshold)
    --delayerror=n  Threshold for fail delay cron warn in minutes (default $delaywarn)

Example:
\$sudo -u www-data /usr/bin/php admin/tool/heartbeat/croncheck.php
";
        die;
    }

} else {
    // If run from the web.
    define('NO_MOODLE_COOKIES', true);
    require($dirroot.'config.php');
    $options = array(
        'cronerror'  => optional_param('cronerror',  $cronthreshold,  PARAM_NUMBER),
        'cronwarn'   => optional_param('cronwarn',   $cronwarn,       PARAM_NUMBER),
        'delayerror' => optional_param('delayerror', $delaythreshold, PARAM_NUMBER),
        'delaywarn'  => optional_param('delaywarn',  $delaywarn,      PARAM_NUMBER),
    );
    header("Content-Type: text/plain");
}


$lastcron = $DB->get_field_sql('SELECT MAX(lastruntime) FROM {task_scheduled}');
$currenttime = time();
$difference = $currenttime - $lastcron;

$testing = get_config('tool_heartbeat', 'testing');
if ($testing == 'error') {
    printf ("CRITICAL: Moodle this is a test\n");
    exit(2);
} else if ($testing == 'warn') {
    printf ("WARNING: Moodle this is a test\n");
    exit(1);
}

if ( $difference > $options['cronerror'] * 60 * 60 ) {
    printf ("CRITICAL: Moodle cron ran > {$options['cronerror']} hours ago\n");
    exit(2);
}

if ( $difference > $options['cronwarn'] * 60 * 60 ) {
    printf ("WARNING: Moodle cron ran > {$options['cronwarn']} hours ago\n");
    exit(1);
}

$delay = '';
$maxdelay = 0;
$tasks = core\task\manager::get_all_scheduled_tasks();
$legacylastrun = null;
foreach ($tasks as $task) {
    if ($task->get_disabled()) {
        continue;
    }
    $faildelay = $task->get_fail_delay();
    if (get_class($task) == 'core\task\legacy_plugin_cron_task') {
        $legacylastrun = $task->get_last_run_time();
    }
    if ($faildelay == 0) {
        continue;
    }
    if ($faildelay > $maxdelay) {
        $maxdelay = $faildelay;
    }
    $delay .= "TASK: " . $task->get_name() . ' (' .get_class($task) . ") Delay: $faildelay\n";
}

if ( empty($legacylastrun) ) {
    printf ( "WARNING: Moodle legacy task isn't running {$options['delaywarn']} mins\n$delay");
    exit(1);
}
$minsincelegacylastrun = floor((time() - $legacylastrun) / 60);
if ( $minsincelegacylastrun > 60 * 24) {
    printf ( "CRITICAL: Moodle legacy task hasn't run in 24 hours\n");
    exit(1);
}
if ( $minsincelegacylastrun > 5) {
    printf ( "WARNING: Moodle legacy task hasn't run in 5 mins\n");
    exit(1);
}

$maxminsdelay = $maxdelay / 60;
if ( $maxminsdelay > $options['delayerror'] ) {
    printf ( "CRITICAL: Moodle task faildelay > {$options['delayerror']} mins\n$delay");
    exit(2);

} else if ( $maxminsdelay > $options['delaywarn'] ) {
    printf ( "WARNING: Moodle task faildelay > {$options['delaywarn']} mins\n$delay");
    exit(1);

} else {
    print "OK: MOODLE CRON RUNNING\n";
    exit(0);
}

