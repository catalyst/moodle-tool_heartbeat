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
    tool_heartbeat\lib::validate_ip_against_config();

    $options = array(
        'cronerror'   => optional_param('cronerror',   $cronthreshold,   PARAM_INT),
        'cronwarn'    => optional_param('cronwarn',    $cronwarn,        PARAM_INT),
        'delayerror'  => optional_param('delayerror',  $delaythreshold,  PARAM_INT),
        'delaywarn'   => optional_param('delaywarn',   $delaywarn,       PARAM_INT),
        'legacyerror' => optional_param('legacyerror', $legacythreshold, PARAM_INT),
        'legacywarn'  => optional_param('legacywarn',  $legacywarn,      PARAM_INT),
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
    $now = userdate(time());
    $next = userdate($nexterror);
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

$when = userdate($lastcron);

if ( $difference > $options['cronerror'] * 60 * 60 ) {
    send_critical("Moodle cron ran > {$options['cronerror']} hours ago\nLast run at $when");
}

if ( $difference > $options['cronwarn'] * 60 * 60 ) {
    send_warning("Moodle cron ran > {$options['cronwarn']} hours ago\nLast run at $when");
}

$delay = '';
$maxdelay = 0;

// Instead of using task API here, we read directly from the database.
// This stops errors originating from broken tasks.
$scheduledtasks = $DB->get_records_sql("SELECT * FROM {task_scheduled} WHERE faildelay > 0 AND disabled = 0");
foreach ($scheduledtasks as $task) {
    $delay .= "SCHEDULED TASK: {$task->classname} Delay: {$task->faildelay}\n";
}

// Instead of using task API here, we read directly from the database.
// This stops errors originating from broken tasks, and allows the DB to de-duplicate them.
$adhoctasks = $DB->get_records_sql("  SELECT classname, COUNT(*) count, MAX(faildelay) faildelay
                                       FROM {task_adhoc}
                                      WHERE faildelay > 0
                                   GROUP BY classname");

foreach ($adhoctasks as $record) {
    // Only add duplicate message if there are more than 1.
    $duplicatemsg = $record->count > 1 ? " ({$record->count} duplicates!!!)" : '';
    $delay .= "ADHOC TASK: {$record->classname} Delay: {$record->faildelay} {$duplicatemsg}\n";
}

// Find the largest faildelay out of both adhoc and scheduled tasks.
$alldelays = array_merge(array_column($adhoctasks, 'faildelay'), array_column($scheduledtasks, 'faildelay'));
$maxdelay = max($alldelays);

$maxminsdelay = $maxdelay / 60;
if ( $maxminsdelay > $options['delayerror'] ) {
    send_critical("Moodle task faildelay > {$options['delayerror']} mins\n$delay");

} else if ( $maxminsdelay > $options['delaywarn'] ) {
    send_warning("Moodle task faildelay > {$options['delaywarn']} mins\n$delay");
}

if ($CFG->branch < 403) {
    $legacytask = \core\task\manager::get_scheduled_task('core\task\legacy_plugin_cron_task');
    $legacylastrun = $legacytask->get_last_run_time();
    if (!$legacylastrun) {
        send_warning("Moodle legacy task isn't running (ie disabled)\n");
    }
    $minsincelegacylastrun = floor((time() - $legacylastrun) / 60); // In minutes.
    $when = userdate($legacylastrun);
    if ( $minsincelegacylastrun > $options['legacyerror']) {
        send_critical("Moodle legacy task last run $minsincelegacylastrun "
            . "mins ago > {$options['legacyerror']} mins\nLast run at $when");
    }
    if ( $minsincelegacylastrun > $options['legacywarn']) {
        send_warning("Moodle legacy task last run $minsincelegacylastrun mins ago > {$options['legacywarn']} mins\nLast run at $when");
    }
}

// If the Check API from 3.9 exists then call those as well.
if (class_exists('\core\check\manager')) {

    if (isset($CFG->mnet_dispatcher_mode) and $CFG->mnet_dispatcher_mode !== 'off') {
        // This is a core bug workaround, see MDL-77247 for more details.
        require_once($CFG->dirroot.'/mnet/lib.php');
    }

    try {
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

                // If we have an error, how should we handle it.
                if ($status == \core\check\result::ERROR && !$critical) {
                    $mapping = get_config('tool_heartbeat', 'errorcritical');
                    if ($mapping === 'critical') {
                        $critical = true;
                    } else if ($mapping === 'criticalbusiness') {
                        // Here we should only set the critical flag between 0900 and 1700 server time.
                        $time = new DateTime('now', core_date::get_server_timezone_object());
                        $hour = (int) $time->format('H');
                        $critical = ($hour >= 9 && $hour < 17);
                    }
                } else if (!$critical) {
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
    } catch (\Throwable $e) {
        $critical = true;
        $output .= "Error scanning checks: " . $e . "\n";
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
