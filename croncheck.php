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

// @codingStandardsIgnoreStart
define('NO_UPGRADE_CHECK', true);

$cronthreshold   = 6;   // Hours.
$cronwarn        = 2;   // Hours.
$delaythreshold  = 600; // Minutes.
$delaywarn       = 60;  // Minutes.
$legacythreshold = 60 * 6; // Minute.
$legacywarn      = 60 * 2; // Minutes.

// @codingStandardsIgnoreEnd

$dirroot = __DIR__ . '/../../../';

if (isset($argv)) {
    // If run from the CLI.
    define('CLI_SCRIPT', true);

    $last = $argv[count($argv) - 1];
    if (preg_match("/(.*):(.+)/", $last, $matches)) {
        $last = $matches[1];
    }
    if ($last && is_dir($last) ) {
        $dirroot = $last . '/';
        array_pop($_SERVER['argv']);
    }

    require($dirroot.'config.php');
    require_once(__DIR__.'/nagios.php');
    require_once($CFG->libdir.'/clilib.php');

    list($options, $unrecognized) = cli_get_params(
        array(
            'help' => false,
            'cronwarn'    => $cronwarn,
            'cronerror'   => $cronthreshold,
            'delaywarn'   => $delaywarn,
            'delayerror'  => $delaythreshold,
            'legacywarn'  => $legacywarn,
            'legacyerror' => $legacythreshold,
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
    --cronwarn=n    Threshold for no cron run error in hours (default $cronwarn)
    --cronerror=n   Threshold for no cron run warn in hours (default $cronthreshold)
    --delaywarn=n   Threshold for fail delay cron error in minutes (default $delaywarn)
    --delayerror=n  Threshold for fail delay cron warn in minutes (default $delaythreshold)
    --legacywarn=n  Threshold for legacy cron warn in minutes (default $legacywarn)
    --legacyerror=n Threshold for legacy cron error in minutes (default $legacythreshold)

Example:
\$sudo -u www-data /usr/bin/php admin/tool/heartbeat/croncheck.php
";
        die;
    }

} else {
    // If run from the web.
    define('NO_MOODLE_COOKIES', true);
    // Add requirement for IP validation.
    require($dirroot.'config.php');
    require_once(__DIR__.'/nagios.php');
    require_once(__DIR__.'/iplock.php');
    $options = array(
        'cronerror'   => optional_param('cronerror',   $cronthreshold,   PARAM_NUMBER),
        'cronwarn'    => optional_param('cronwarn',    $cronwarn,        PARAM_NUMBER),
        'delayerror'  => optional_param('delayerror',  $delaythreshold,  PARAM_NUMBER),
        'delaywarn'   => optional_param('delaywarn',   $delaywarn,       PARAM_NUMBER),
        'legacyerror' => optional_param('legacyerror', $legacythreshold, PARAM_NUMBER),
        'legacywarn'  => optional_param('legacywarn',  $legacywarn,      PARAM_NUMBER),
    );
    header("Content-Type: text/plain");

    // Make sure varnish doesn't cache this. But it still might so go check it!
    header('Pragma: no-cache');
    header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
    header('Expires: Tue, 04 Sep 2012 05:32:29 GMT');
}

if (isset($CFG->adminsetuppending)) {
    send_critical("Admin setup pending, please set up admin account");
}

if (moodle_needs_upgrading()) {
    $upgraderunning = get_config(null, 'upgraderunning');
    $initialinstall = during_initial_install();

    $difference = format_time((time() > $upgraderunning ? (time() - $upgraderunning) : 300));

    if (!$upgraderunning) {
        send_critical("Moodle upgrade pending and is not running, cron execution suspended");
    }

    if ($upgraderunning >= time()) {
        // Before the expected finish time.
        if (!empty($initialinstall)) {
            send_critical("Moodle installation is running, ETA > $difference, cron execution suspended");
        } else {
            send_critical("Moolde upgrade is running, ETA > $difference, cron execution suspended");
        }
    }

    /*
     * After the expected finish time (timeout or other interruption)
     * The "core_shutdown_manager::register_function('upgrade_finished_handler');" already handle these cases
     * and unset config 'upgraderunning'
     * The below critical ones can happen if core_shutdown_manager fails to run the handler function.
     */
    if (!empty($initialinstall)) {
        send_critical("Moodle installation is running, overdue by $difference ");
    } else {
        send_critical("Moodle upgrade is running, overdue by $difference ");
    }
}

// We want to periodically emit an error_log which we will detect elsewhere to
// confirm that all the various web server logs are not stale.
$nexterror = get_config('tool_heartbeat', 'nexterror');
$errorperiod = get_config('tool_heartbeat', 'errorlog');
if (!$errorperiod) {
    $errorperiod = 30 * MINSECS;
}
if (!$nexterror || time() > $nexterror) {
    $nexterror = time() + $errorperiod;
    $now = userdate(time(), $format);
    $next = userdate($nexterror, $format);
    $period = format_time($errorperiod);
    // @codingStandardsIgnoreStart
    error_log("heartbeat test $now, next test expected in $period at $next");
    // @codingStandardsIgnoreEnd
    set_config('nexterror', $nexterror, 'tool_heartbeat');
}

if ($CFG->branch < 27) {

    $lastcron = $DB->get_field_sql('SELECT MAX(lastcron) FROM {modules}');
    $currenttime = time();
    $difference = $currenttime - $lastcron;

    if ( $difference > $options['cronerror'] * 60 * 60 ) {
        send_critical("Moodle cron ran > {$options['cronerror']} hours ago\nLast run at $when");
    }

    if ( $difference > $options['cronwarn'] * 60 * 60 ) {
        send_warning("Moodle cron ran > {$options['cronwarn']} hours ago\nLast run at $when");
    }

    send_good("MOODLE CRON RUNNING\n");
}

$lastcron = $DB->get_field_sql('SELECT MAX(lastruntime) FROM {task_scheduled}');
$currenttime = time();
$difference = $currenttime - $lastcron;

$testing = get_config('tool_heartbeat', 'testing');
if ($testing == 'error') {
    send_critical("Moodle this is a test $CFG->wwwroot/admin/settings.php?section=tool_heartbeat\n");
} else if ($testing == 'warn') {
    send_warning("Moodle this is a test $CFG->wwwroot/admin/settings.php?section=tool_heartbeat\n");
}

$when = userdate($lastcron, $format);

if ( $difference > $options['cronerror'] * 60 * 60 ) {
    send_critical("Moodle cron ran > {$options['cronerror']} hours ago\nLast run at $when");
}

if ( $difference > $options['cronwarn'] * 60 * 60 ) {
    send_warning("Moodle cron ran > {$options['cronwarn']} hours ago\nLast run at $when");
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
    $delay .= "SCHEDULED TASK: " . get_class($task) . ' (' .$task->get_name() . ") Delay: $faildelay\n";
}

$records = $DB->get_records_sql('SELECT * from {task_adhoc} WHERE faildelay > 0');
foreach ($records as $record) {
    $task = \core\task\manager::adhoc_task_from_record($record);
    if (!$task) {
        continue;
    }

    $faildelay = $task->get_fail_delay();
    if ($faildelay == 0) {
        continue;
    }
    if ($faildelay > $maxdelay) {
        $maxdelay = $faildelay;
    }
    $delay .= "ADHOC TASK: " .get_class($task) . " Delay: $faildelay\n";
}

if ( empty($legacylastrun) ) {
    send_warning("Moodle legacy task isn't running (ie disabled)\n");
}
$minsincelegacylastrun = floor((time() - $legacylastrun) / 60); // In minutes.
$when = userdate($legacylastrun, $format);

if ( $minsincelegacylastrun > $options['legacyerror']) {
    send_critical("Moodle legacy task last run $minsincelegacylastrun "
        . "mins ago > {$options['legacyerror']} mins\nLast run at $when");
}
if ( $minsincelegacylastrun > $options['legacywarn']) {
    send_warning("Moodle legacy task last run $minsincelegacylastrun mins ago > {$options['legacywarn']} mins\nLast run at $when");
}

$maxminsdelay = $maxdelay / 60;
if ( $maxminsdelay > $options['delayerror'] ) {
    send_critical("Moodle task faildelay > {$options['delayerror']} mins\n$delay");

} else if ( $maxminsdelay > $options['delaywarn'] ) {
    send_warning("Moodle task faildelay > {$options['delaywarn']} mins\n$delay");
}

// If the Check API from 3.9 exists then call those as well:
if (class_exists('\core\check\manager')) {

    $checks = \core\check\manager::get_checks('status');
    $output = '';
    // Should this check block emit as critical?
    $critical = false;

    foreach ($checks as $check) {
        $ref = $check->get_ref();
        $result = $check->get_result();

        $status = $result->get_status();

        // Summary is treated as html.
        $summary = $result->get_summary();
        $summary = html_to_text($summary, 80, false);

        if ($status == \core\check\result::WARNING ||
            $status == \core\check\result::CRITICAL ||
            $status == \core\check\result::ERROR) {

            if (!$critical) {
                $critical = $status == \core\check\result::CRITICAL;
            }

            $output .= $check->get_name() . "\n";
            $output .= "$summary\n";

            $detail = new moodle_url('/report/status/index.php', ['detail' => $ref]);
            $output .= 'Details: ' . $detail->out() . "\n\n";

            $link = $check->get_action_link();
            if ($link) {
                $output .= $link->url . "\n";
            }
        }
    }

    // Strictly some of these could a critical but softly softly.
    if ($output) {
        // For now emit only criticals as criticals. Error status should be a critical later.
        if ($critical) {
            send_critical($output);
        } else {
            send_warning($output);
        }
    }

}

send_good("MOODLE CRON RUNNING\n");
