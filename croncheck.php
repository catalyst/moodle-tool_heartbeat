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
 * Check API Health Check
 *
 * @package    tool_heartbeat
 * @copyright  2023 Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * See also:
 *  - http://nagios.sourceforge.net/docs/3_0/pluginapi.html
 *  - https://nagios-plugins.org/doc/guidelines.html#PLUGOUTPUT
 */


// @codingStandardsIgnoreStart
define('NO_UPGRADE_CHECK', true);
define('NO_MOODLE_COOKIES', true);

// Detect if web or CLI.
$isweb = !isset($argv);
$iscli = !$isweb;

// CLI must define this before including config.php
if ($iscli) {
    define('CLI_SCRIPT', true);
}

$dirroot = __DIR__ . '/../../../';
require_once($dirroot . 'config.php');

if ($isweb) {
    // If run from the web.
    // Add requirement for IP validation.
    tool_heartbeat\lib::validate_ip_against_config();

    header("Content-Type: text/plain");

    // Ensure its not cached.
    header('Pragma: no-cache');
    header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
    header('Expires: Tue, 04 Sep 2012 05:32:29 GMT');
}

use tool_heartbeat\checker;
use tool_heartbeat\lib;

global $PAGE;

if (isset($CFG->mnet_dispatcher_mode) and $CFG->mnet_dispatcher_mode !== 'off') {
    // This is a core bug workaround, see MDL-77247 for more details.
    require_once($CFG->dirroot.'/mnet/lib.php');
}

// Start output buffering. This stops for e.g. debugging messages from breaking the output.
// The checker class collects this, and if anything it output it shows a warning.
ob_start();

lib::process_error_log_ping();

$messages = checker::get_check_messages();

// Construct the output message.
$PAGE->set_context(\context_system::instance());

// Indent the messages.
$msg = array_map(function($message) {
    global $OUTPUT;
    
    $spacer = " ";

    // Add the spacer to the start of each message line.
    $indentedlines = explode("\n", $message->message);
    $indentedlines = array_map(function($line) use ($spacer) {
        return $spacer . $line;
    }, $indentedlines);
    
    $indentedmessage = implode("\n", $indentedlines);

    return $OUTPUT->render_from_template('tool_heartbeat/resultmessage', [
        'prefix' => checker::NAGIOS_PREFIXES[$message->level],
        'title' => $message->title,
        'message' => $indentedmessage,
    ]);
}, $messages);

$msg = checker::create_summary($messages) . "\n" . implode("\n\n", $msg);
$msg = htmlspecialchars_decode($msg);

$level = checker::determine_nagios_level($messages);
$prefix = checker::NAGIOS_PREFIXES[$level];
$now = userdate(time());

printf("{$prefix}: $msg\n\n(Checked {$now})\n");
exit($level);
