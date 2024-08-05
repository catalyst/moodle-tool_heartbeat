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
 * Are you Ok? heartbeat for load balancers etc
 *
 * @package    tool_heartbeat
 * @copyright  2014 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Heartbeat';
$string['errorlog'] = 'Error log period';
$string['errorlogdesc'] = 'To help ensure that all web server logging is working we can emit an intermittent message to the error_log. Set this to 0 to turn it off.';
$string['normal'] = 'Normal monitoring';
$string['progress'] = 'Progress bar test';
$string['progresshelp'] = 'This tests that all the various output buffers in the entire stack are corrent including but not limited to php, ob, gzip/deflat, varnish, nginx etc';
$string['testabort'] = 'Test abort';
$string['testaborthelp'] = '<p>This tests wether the whole stack is correctly passing a request cancel signal from the browser to php through whatever layers are in the middle, such as load balancers, cdn\'s, caches, php-fm etc.</p>
<p>It works in 2 stages:<p>
<ol>
<li>Stage one takes 10 seconds and renders a progress bar</ii>
<li>In javascript the progress bar page is aborted after 1 second</li>
<li>If the abort works fully the process is killed which unlocks the session and the page reloads quickly showing where the progress bar had gotten to.</li>
<li>If the abort did not work it will continue as an orphaned process holding the session lock until it times out after 5 seconds</li>
</ol>
';
$string['testerror'] = 'Fake a critical';
$string['testwarning'] = 'Fake a warning';
$string['testing'] = 'Test heartbeat';
$string['testingdesc'] = 'You can use this to temporarily fake a warn or error condition to test that your monitoring is correctly working end to end.';
$string['allowedips'] = 'Allowed IPs Config';
$string['allowedipstitle'] = 'IP Blocking Configuration';
$string['allowedipsdescription'] = 'Box to enter safe IP addresses for the heartbeat to respond to.';
$string['allowedipsempty'] = 'When the allowed IPs list is empty we will not block anyone. You can add your own IP address (<i>{$a->ip}</i>) and block all other IPs.';
$string['allowedipshasmyip'] = 'Your IP (<i>{$a->ip}</i>) is in the list and you will not be blocked from checking the heartbeat.';
$string['allowedipshasntmyip'] = 'Your IP (<i>{$a->ip}</i>) is not in the list and you will be blocked from checking the heartbeat.';
$string['allowedipsnoconfig'] = 'Your config.php does not have the extra setup to allow blocking via IP.<br />Please refer to our <a href="https://github.com/catalyst/moodle-auth_outage#installation" target="_blank">README.md</a> file for more information.';
$string['emptyautherror'] = 'Auth methods empty, config lost. Previous value: {$a}';
$string['configauthmissing'] = 'Configured auth methods are not currently enabled.';
$string['setinitialauthstate'] = 'Initial auth state for heartbeat auth check set.';
$string['authcorrect'] = 'Auth methods correctly configured.';
$string['builtinallowediplist'] = 'Builtin IP Blocking Configuration';
$string['builtinallowediplist_desc'] = 'This allowed IP list would allow some IPs to be editable in the UI in addition to those forced in config.php.';
$string['configuredauths'] = 'Check auth methods';
$string['configuredauthsdesc'] = 'Auth methods to check are enabled in the Check API. A warning will be emitted if they are not enabled.';
$string['checkauthcheck'] = 'Authentication methods';
$string['checkcachecheck'] = 'Cache consistency check';
$string['checkcachecronmissing'] = 'The cron cache check has not succeeded yet or is missing';
$string['checkcachedetails'] = 'A split brain cache was detected. The value stored in the database table config_plugins was not the same as the cached value returned from get_config. If you purge the cache and this check passes and then fails again after a few hours then that strongly suggests a cache misconfiguration.';
$string['checkcacheerrorsplit'] = 'The caches are not consistent: {$a}';
$string['checkcachenotsplit'] = 'Caches appear consistent between web and cron';
$string['checkcachewebmissing'] = 'The web cache check has not succeeded yet';
$string['checkdnscheck'] = 'DNS wwwroot check';
$string['checkrangerequestcheck'] = 'Range requests check';
$string['checkrangerequestok'] = 'Range requests are working, 206 response with only 10 bytes of data';
$string['checkrangerequestbad'] = 'Range requests are bad! HTTP {$a->code} response with only {$a->bytes} bytes of data for {$a->url}';
$string['checklogstorecheck'] = 'Logstore check';
$string['checklogstoreok'] = 'Logstore checks are OK. One or more logstores are active.';
$string['checklogstorebad'] = 'Logstore checks are bad! Please ensure at least one logstore has been set and enabled.';
$string['ips_combine'] = 'The IPs listed above will be combined with the IPs listed below.';
$string['errorascritical'] = ' Report check errors as:';
$string['errorascritical_desc'] = 'This setting controls what check API errors are reported as in Nagios. "CRITICAL" is the most noisy, and "WARNING" is the least noisy for monitoring endpoints. Business hours is 9AM - 5PM in the server timezone ({$a}).';
$string['error_critical'] = 'CRITICAL';
$string['error_critical_business'] = 'CRITICAL during business hours';
$string['error_warning'] = 'WARNING';
$string['tasklatencymonitoring'] = 'Task monitoring';
$string['tasklatencymonitoring_desc'] = 'Enter configuration for monitoring specific cron tasks. Enter each task configuration on a new line. Configuration format is <code>\component\task\classname, (integer) runtime, (integer) starttimedrift, (integer) notrunning</code>. Runtime is the max runtime that an invididual run of a task cannot exceed. Start time drift is the drift from the scheduled run time of a task before the task begins execution. Not running is the configured time period for which a task must have run within. E.g. <code>{$a}</code>';
$string['latencydelayedstart'] = 'Task {$a->task} start is delayed past configured threshold: {$a->mins}.';
$string['latencynotrun'] = 'Task {$a->task} has not run within the configured latency threshold: {$a->mins}.';
$string['latencyruntime'] = 'Task {$a->task} was last run with a runtime longer than the configured threshold: {$a->mins}.';
$string['checktasklatencycheck'] = 'Task latency check';
$string['taskconfigbad'] = 'Bad configurations {$a}';
$string['tasklatencyok'] = 'Task latency OK.';
$string['checkfailingtaskcheck'] = 'Failing tasks';
$string['checkfailingtaskchecktask'] = 'Failing task: {$a}';
$string['checkfailingtaskok'] = '{$a} tasks running OK.';
$string['checkdirsizes'] = 'CFG->dataroot size';
$string['mute'] = 'Mute';
$string['unmute'] = 'Unmute';
$string['expiresat'] = 'Muted until';
$string['override'] = 'Display status';
$string['addmute'] = 'Add mute';
$string['editmute'] = 'Edit mute';
$string['overriderestore'] = 'Fields have been pre-filled with information from a previous override.';
$string['noterequired'] = 'Please add some notes';
$string['muteurlregex'] = 'The URL must match the format defined in settings: {$a}';
$string['statusunknown'] = 'Unknown';
$string['settings:mutedefault'] = 'Default mute duration';
$string['settings:mutedefault:desc'] = 'Adjust the default duration of a check mute.';
$string['settings:mutedefaultstatus'] = 'Default mute status';
$string['settings:mutedefaultstatus:desc'] = 'Adjust the default status of a check mute.';
$string['settings:muteurlregex'] = 'Mute URL regex rule';
$string['settings:muteurlregex:desc'] = 'Adds a regex matching rule for check mute URLs and makes it a required field. The provided regex should include delimiters.';

$string['settings:cachecheckheading'] = 'Cache consistency check';
$string['settings:shouldlogcacheping:heading'] = 'Log cache ping';
$string['settings:shouldlogcacheping:desc'] = 'If enabled, whenever the cache ping is updated (usually once every 24 hrs), a <code>cache_ping</code> event will be triggered';
$string['settings:shouldlogcachecheck:heading'] = 'Log cache check';
$string['settings:shouldlogcachecheck:desc'] = 'If enabled, whenever the cache ping is checked (whenever the <code>cachecheck</code> check is executed) a <code>cache_check</code> event will be triggered';
$string['systemstatus'] = 'Heartbeat system status';

/*
 * Privacy provider (GDPR)
 */
$string['privacy:metadata:tool_heartbeat_overrides'] = 'Heartbeat Overrides';
$string['privacy:metadata:tool_heartbeat_overrides:note'] = 'Override note added by user';
$string['privacy:metadata:tool_heartbeat_overrides:url'] = 'Override URL added by user';
$string['privacy:metadata:tool_heartbeat_overrides:userid'] = 'User who created the override';
$string['privacy:metadata:tool_heartbeat_overrides:usermodified'] = 'User who modified the override';
$string['privacy:metadata:tool_heartbeat_overrides:timecreated'] = 'Timestamp when the override was created';
$string['privacy:metadata:tool_heartbeat_overrides:timemodified'] = 'Timestamp when the override was modified';
