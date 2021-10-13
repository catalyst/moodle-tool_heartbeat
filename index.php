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
 * Are you Ok? heartbeat for load balancers
 *
 * @package    tool_heartbeat
 * @copyright  2014 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Make sure varnish doesn't cache this. But it still might so go check it!
// @codingStandardsIgnoreStart
header('Pragma: no-cache');
header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
header('Expires: Tue, 04 Sep 2012 05:32:29 GMT');
// @codingStandardsIgnoreEnd

// Set this manually to true as needed.
if (false) {
    print "Server is in MAINTENANCE";
    exit;
}


$fullcheck = false;
$checksession = false;

if (isset($argv) && $argv[0]) {
    define('CLI_SCRIPT', true);
    $fullcheck = count($argv) > 1 && $argv[1] === 'fullcheck';
} else {
    define('NO_MOODLE_COOKIES', true);
    define('CLI_SCRIPT', false);
    $fullcheck = isset($_GET['fullcheck']);
}
if (!defined('CLI_SCRIPT')) {
    $checksession = isset($_GET['checksession']);
}
define('NO_UPGRADE_CHECK', true);
define('ABORT_AFTER_CONFIG', true);

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
    $content = preg_replace("/;/", ";\n", $content);         // Split up statements, replace ';' with ';\n'
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

if (check_climaintenance(__DIR__ . '/../../../config.php') === true) {
    print "Server is in MAINTENANCE<br>\n";
    exit;
}

require_once(__DIR__ . '/../../../config.php');
global $CFG;

// Maintenance can also be enabled in the database config. Check for it otherwise we'll return a 503 to the heartbeat request
if (!empty($CFG->maintenance_enabled)) {
    print "Server is in MAINTENANCE<br>\n";
    exit;
}

$status = "";

/**
 * Return an error that ELB will pick up
 *
 * @param string $reason
 */
function failed($reason) {
    // Status for ELB, will cause ELB to remove instance.
    header("HTTP/1.0 503 Service unavailable: failed $reason check");
    // Status for the humans.
    print "Server is DOWN<br>\n";
    echo "Failed: $reason";
    exit;
}

$testfile = $CFG->dataroot . "/tool_heartbeat.test";
$size = file_put_contents($testfile, '1');
if ($size !== 1) {
    failed('sitedata not writable');
}

if (file_exists($testfile)) {
    $status .= "sitedata OK<br>\n";
} else {
    failed('sitedata not readable');
}

define('ABORT_AFTER_CONFIG_CANCEL', true);
require($CFG->dirroot . '/lib/setup.php');
require_once($CFG->libdir.'/filelib.php');

// IP Locking, check for CLI, check for remote IP in validated list, if not, exit.
if (!(isset($argv))) {
    require_once('iplock.php');
}

if ($fullcheck || $checksession) {
    $c = new curl(array('cache' => false, 'cookie' => true));
    $response = $c->get(new moodle_url('/admin/tool/heartbeat/sessionone.php'));
    if ($sessioncheck = json_decode($response)) {
        if ($sessioncheck->success == 'pass') {
            if ($sessioncheck->latency > 5) {
                failed("Session latency outside of acceptable range: {$sessioncheck->latency} seconds.");
            }
            $status .= "Session check OK<br>\n";
        } else {
            failed("Session check FAIL, "
                . "Request host: {$sessioncheck->requesthost}, "
                . "Response host: {$sessioncheck->responsehost}, "
                . "Latency (seconds): {$sessioncheck->latency}");
        }
    } else {
        failed('Session check could not be conducted, error connecting to session check URL');
    }
}

$sessionhandler = (property_exists($CFG, 'session_handler_class') && $CFG->session_handler_class === '\core\session\memcached');
$savepath = property_exists($CFG, 'session_memcached_save_path');

if ($sessionhandler && $savepath) {
    require_once($CFG->libdir . '/classes/session/util.php');
    $servers = \core\session\util::connection_string_to_memcache_servers($CFG->session_memcached_save_path);
    try {
        $memcached = new \Memcached();
        $memcached->addServers($servers);
        $stats = $memcached->getStats();
        $memcached->quit();

        $addr = $servers[0][0];
        $port = $servers[0][1];

        if ($stats[$addr . ':' . $port]['uptime'] > 0) {
            $status .= "session memcached OK<br>\n";
        } else {
            failed('sessions memcached');
        }

    } catch (Exception $e) {
        failed('sessions memcached');
    } catch (Throwable $e) {
        failed('sessions memcached');
    }

}

// Optionally check database configuration and access (slower).
if ($fullcheck) {
    try {
        global $DB;
        // Try to get the first record from the user table.
        $user = $DB->get_record_sql('SELECT id FROM {user} WHERE 0 < id ', null, IGNORE_MULTIPLE);
        if ($user) {
            $status .= "database OK<br>\n";
        } else {
            failed('no users in database');
        }
    } catch (Exception $e) {
        failed('database error');
    } catch (Throwable $e) {
        failed('database error');
    }
}

print "Server is ALIVE<br>\n";
print $status;

