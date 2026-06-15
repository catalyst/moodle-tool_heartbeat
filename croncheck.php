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

if (substr($_SERVER['SCRIPT_FILENAME'], -42) == '/public/admin/tool/heartbeat/croncheck.php') {
    // We are in Moodle 5.2 under the public/ sub path.
    $dirroot = __DIR__ . '/../../../../';
}

/**
 * Checks if the command line maintenance mode has been enabled. Skip the config bootstrapping.
 *
 * @param string $configfile The relative path for config.php
 * @return bool True if climaintenance.html is found.
 */
function check_climaintenance($configfile) {
    $content = file_get_contents($configfile);

    // Set comments to be on newlines, replace '//' with '\n//', where // does not start with a : colon.
    $content = preg_replace("#[^!:]//#", "\n//", $content);
    $content = preg_replace("/;/", ";\n", $content);         // Split up statements, replace ';' with ';\n'.
    $content = preg_replace("/^[\s]+/m", "", $content);      // Removes all initial whitespace and newlines.

    $re = '/^\$CFG->dataroot\s+=\s+["\'](.*?)["\'];/m';  // Lines starting with $CFG->dataroot.
    preg_match($re, $content, $matches);
    if (!empty($matches)) {
        $climaintenance = $matches[count($matches) - 1] . '/climaintenance.html';

        if (file_exists($climaintenance)) {
            return true;
        }
    }

    return false;
}

if (check_climaintenance($dirroot . 'config.php') === true) {
    print "CRITICAL: Moodle is in hard cli maintenance mode\n";
    exit;
}

require_once($dirroot . 'config.php');

if (!empty($CFG->maintenance_enabled)) {
    print "CRITICAL: Moodle is in soft maintenance mode\n";
    exit;
}

$filterids = [];
if ($isweb) {
    // If run from the web.
    // Add requirement for IP validation.
    tool_heartbeat\lib::validate_ip_against_config();

    $filterraw = optional_param('filter', '', PARAM_RAW_TRIMMED);
    if (!empty($filterraw)) {
        foreach (explode(',', $filterraw) as $id) {
            $id = trim($id);
            if ($id !== '') {
                $filterids[$id] = true;
            }
        }
    }

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

$messages = checker::get_check_messages($filterids);

// Construct the output message.
$PAGE->set_context(\context_system::instance());

// Indent the messages.
$msg = array_map(function($message) {
    global $OUTPUT;

    $spacer = '    ';

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

echo "{$prefix}: $msg\n\n";
if ($filterids) {
    echo "Filtered to subset of checks: " . join(', ', array_keys($filterids)) . " \n";
}
echo "(Checked {$now})\n";
exit($level);
