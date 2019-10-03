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
 *
 * check objectfs connection
 * @package    tool_heartbeat
 * @author   2019 Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL');
define('NO_UPGRADE_CHECK', true);

use tool_objectfs\local\report\objectfs_report_builder;

$longoptions = array(
    'help' => false,
    'missthres' => 0,
);

$shortoptions = array(
    'h' => 'help',
    'mt' => 'missthres',
);

if (isset($argv)) {
    // If run from the CLI.
    define('CLI_SCRIPT', true);
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__.'/nagios.php');
    require_once($CFG->libdir . '/clilib.php');
    list($options, $unrecognized) = cli_get_params($longoptions, $shortoptions);
    if ($unrecognized) {
        $unrecognized = implode("\n  ", $unrecognized);
        cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
    }

    if ($options['help']) {
        print "Checks objectfs connection

        objectfscheck.php [options]

        Options:
        -h, --help            Print out this help
        -mt, --missthres      Number of missing files threshold, Default is 0 - not checking missing file

        Example:
        \$sudo -u www-data /usr/bin/php admin/tool/heartbeat/objectfscheck.php\n";

        die;
    }
} else {
    // If run from the web.
    define('NO_MOODLE_COOKIES', true);
    require_once(__DIR__ . '/../../../config.php');
    require_once(__DIR__.'/nagios.php');
    require_once(__DIR__.'/iplock.php');

    $options['missthres'] = optional_param('missthres', 0, PARAM_INT);

    header("Content-Type: text/html");
    // Make sure varnish doesn't cache this. But it still might so go check it!
    header('Pragma: no-cache');
    header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
    header('Expires: Tue, 04 Sep 2012 05:32:29 GMT');
}

$objectfslib = __DIR__ . '/../objectfs/lib.php';
if (file_exists($objectfslib)) {
    require_once(__DIR__ . '/../objectfs/lib.php');
    $config = get_objectfs_config();

    if (isset($CFG->alternative_file_system_class)) {
        if ($CFG->alternative_file_system_class != $config->filesystem) {
            send_critical("OBJECTFS - Mismatched file system with " . '$CFG->alternative_file_system_class' . "\n");
        }
    }

    $client = tool_objectfs_get_client($config);
    if (empty($client)) {
        send_critical("OBJECTFS - No clients\n");
    }
    $connection = $client->test_connection();
    if (!$connection->success) {
        send_critical("OBJECTFS - Connection failed\n");
    } else {
        $permission = $client->test_permission(true);
        if (!$permissions->success) {
            send_critical("OBJECTFS - Permissions failed\n");
        }
    }

    $missthres = ($options['missthres']);
    if ($missthres > 0) {
        $report = objectfs_report_builder::load_report_from_database('location');
        if (!empty($report)) {
            foreach ($report->get_rows() as $row) {
                if ($row->datakey == OBJECT_LOCATION_ERROR && $row->objectcount >= $missthres) {
                    send_critical("OBJECTFS - Number of missing files is above the threshold ($missthres files): $row->objectcount \n");
                }
            }
        }
    }
} else {
    send_warning("OBJECTFS - The plugin does not appear to be installed");
}

send_good("OBJECTFS - Status OK\n");
