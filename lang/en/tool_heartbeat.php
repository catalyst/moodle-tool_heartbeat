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
$string['testing'] = 'Test heartbeat';
$string['testingdesc'] = 'You can use this to temporarily fake a warn or error condition to test that your monitoring is correctly working end to end.';
$string['normal'] = 'Normal monitoring';
$string['progress'] = 'Progress bar test';
$string['progresshelp'] = 'This tests that all the various output buffers in the entire stack are corrent including but not limited to php, ob, gzip/deflat, varnish, nginx etc';
$string['testwarning'] = 'Fake a warning';
$string['testerror'] = 'Fake a critical';
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
$string['checkrangerequestcheck'] = 'Range requests check';
$string['checkrangerequestok'] = 'Range requests are working, 206 response with only 10 bytes of data';
$string['checkrangerequestbad'] = 'Range requests are bad! HTTP {$a->code} response with only {$a->bytes} bytes of data for {$a->url}';
$string['ips_combine'] = 'The IPs listed above will be combined with the IPs listed below.';

/*
 * Privacy provider (GDPR)
 */
$string["privacy:no_data_reason"] = "The Heartbeat plugin does not store any personal data.";
