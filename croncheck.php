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

$CRON_THRESHOLD  = 6;   // hours
$CRON_WARN       = 2;   // hours
$DELAY_THRESHOLD = 600; // minutes
$DELAY_WARN      = 60;  // minutes

$MOODLE_ROOT = '../../../';


// If run from the CLI
if ($argv){
    define('CLI_SCRIPT', true);

    $last = $argv[sizeof($argv)-1];
    if ($last && is_dir($last) ) {
        $MOODLE_ROOT = array_pop($argv).'/';
        array_pop($_SERVER['argv']);
    }


    require($MOODLE_ROOT.'config.php');
    require_once($CFG->libdir.'/clilib.php');      // cli only functions

    // now get cli options
    list($options, $unrecognized) = cli_get_params(
        array(
            'help'=>false,
            'cronwarn'   => $CRON_THRESHOLD,
            'cronerror'  => $CRON_WARN,
            'delaywarn'  => $DELAY_THRESHOLD,
            'delayerror' => $DELAY_WARN
        ),
        array(
            'h'   =>'help'
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
    --cronwarn=n    Threshold for no cron run error in hours (default $CRON_THRESHOLD)
    --cronerror=n   Threshold for no cron run warn in hours (default $CRON_WARN)
    --delaywarn=n   Threshold for fail delay cron error in minutes (default $DELAY_THRESHOLD)
    --delayerror=n  Threshold for fail delay cron warn in minutes (default $DELAY_WARN)

Example:
\$sudo -u www-data /usr/bin/php admin/tool/heartbeat/croncheck.php
";
        die;
    }

// If run from the web
} else {
    define('NO_MOODLE_COOKIES', true);
    require($MOODLE_ROOT.'config.php');
    $options = array(
        'cronerror'  => optional_param('cronerror',  $DEFAULT_THRESHOLD, PARAM_NUMBER),
        'cronwarn'   => optional_param('cronwarn',   $DEFAULT_WARN,      PARAM_NUMBER),
        'delayerror' => optional_param('delayerror', $DEFAULT_THRESHOLD, PARAM_NUMBER),
        'delaywarn'  => optional_param('delaywarn',  $DEFAULT_WARN,      PARAM_NUMBER),
    );
    header("Content-Type: text/plain");
}


$lastcron = $DB->get_field_sql('SELECT MAX(lastruntime) FROM {task_scheduled}');
$currenttime = time();
$difference = $currenttime - $lastcron;

if( $difference > $options['cronerror'] * 60 * 60 ) {
    printf ("CRITICAL: MOODLE CRON ERROR | LAST RAN %d days %02d:%02d hours AGO (> {$options['cronerror']} hours)\n",
        floor($difference/60/60/24),
        floor($difference/60/60) % 24,
        floor($difference/60) % 60
    );
    exit(2);
}

if( $difference > $options['cronwarn'] * 60 * 60 ) {
    printf ("WARNING: MOODLE CRON WARNING | LAST RAN %d days %02d:%02d hours AGO (> {$options['cronwarn']} hours)\n",
        floor($difference/60/60/24),
        floor($difference/60/60) % 24,
        floor($difference/60) % 60
    );
    exit(1);
}

$delay = '';
$maxdelay = 0;
$tasks = core\task\manager::get_all_scheduled_tasks();
foreach ($tasks as $task) {
    if ($task->get_disabled()) {
        continue;
    }
    $faildelay = $task->get_fail_delay();
    if ($faildelay == 0){
        continue;
    }
    if ($faildelay > $maxdelay){
        $maxdelay = $faildelay;
    }
    $delay .= "TASK: " . $task->get_name() . ' (' .get_class($task) . ") Delay: $faildelay\n";
}

$maxminsdelay = $maxdelay / 60;
if( $maxminsdelay > $options['delayerror'] ) {
    printf ( "CRITICAL: MOODLE CRON TASK FAIL DELAYS | Max delay = %02d:%02d ($maxminsdelay > {$options['delayerror']} minutes) \n$delay",
        floor($maxminsdelay/60) % 60,
        floor($maxminsdelay) % 60
    );
    exit(2);

} else if( $maxminsdelay > $options['delaywarn'] ) {
    printf ( "WANRING: MOODLE CRON TASK FAIL DELAYS | Max delay = %02d:%02d ($maxminsdelay > {$options['delaywarn']} minutes) \n$delay",
        floor($maxminsdelay/60) % 60,
        floor($maxminsdelay) % 60
    );
    exit(1);

} else {
    print "OK: MOODLE CRON RUNNING\n";
    exit(0);
}

