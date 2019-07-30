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

use core\session\manager;

require_once(__DIR__ . '/locallib.php');

// Make sure varnish doesn't cache this. But it still might so go check it!
header('Pragma: no-cache');
header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
header('Expires: Tue, 04 Sep 2012 05:32:29 GMT');

// Set this manually to true as needed.
if (false) {
    print "Server is in MAINTENANCE";
    exit;
}

// Default to not include session checks.
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
if (!defined(CLI_SCRIPT)) {
    $checksession = isset($_GET['checksession']);
}
define('NO_UPGRADE_CHECK', true);

if (check_climaintenance(__DIR__ . '/../../../config.php') === true) {
    print "Server is in MAINTENANCE<br>\n";
    exit;
}

define('ABORT_AFTER_CONFIG_CANCEL', true);
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/nagios.php');

global $CFG;

// Initialise status message.
$status = "";

$testfile = $CFG->dataroot . "/tool_heartbeat.test";
$size = file_put_contents($testfile, '1');
if ($size !== 1) {
    send_critical('sitedata not writable');
}

if (file_exists($testfile)) {
    $status .= "sitedata OK<br>\n";
} else {
    send_critical('sitedata not readable');
}

// IP Locking, check for CLI, check for remote IP in validated list, if not, exit.
if (!(isset($argv))) {
    require_once('iplock.php');
}

$sessionclass = manager::get_handler_class();
$sessionclasspatharray = explode('\\', $sessionclass);
$sessiondriver = end($sessionclasspatharray);

// Require the filelib to bootstrap curl.
require_once($CFG->libdir . '/filelib.php');

if ($fullcheck || $checksession) {
    $c = new curl(array('cache' => false, 'cookie' => true));
    $response = $c->get(new moodle_url('/admin/tool/heartbeat/sessionone.php'));
    if ($sessioncheck = json_decode($response)) {
        if ($sessioncheck->success == 'pass') {
            if ($sessioncheck->latency > 5) {
                echo "Session latency outside of acceptable range: {$sessioncheck->latency} seconds.";
                send_warning($sessiondriver . ' session');
            }
            $status .= "Session check OK, Session Handler: " . $sessiondriver . "<br>\n";
        } else {
            echo ("Session check FAIL, "
                . "Request host: {$sessioncheck->requesthost}, "
                . "Response host: {$sessioncheck->responsehost}, "
                . "Latency (seconds): {$sessioncheck->latency}, "
                . "Session Handler: " . $sessiondriver);
            send_critical($sessiondriver . ' session');
        }
    } else {
        echo 'Session check could not be conducted, error connecting to session check URL';
        send_warning($sessiondriver . ' session');
    }
}

// If we are using Redis, check that it's working explicitly.
if ($sessiondriver == 'redis') {
    if (tool_heartbeat_redis_check()) {
        $status .= "Redis check OKAY</br>\n";
    } else {
        send_warning($sessiondriver . ' session');
    }
}

if ($sessiondriver == 'memcached' && property_exists($CFG, 'session_memcached_save_path')) {
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
            send_warning('sessions memcached');
        }
    } catch (Exception $e) {
        send_warning('sessions memcached');
    } catch (Throwable $e) {
        send_warning('sessions memcached');
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
            send_critical('no users in database');
        }
    } catch (Exception $e) {
        send_critical('database error');
    } catch (Throwable $e) {
        send_critical('database error');
    }
}

print "Server is ALIVE<br>\n";
print $status;
send_good('Server is ALIVE');

