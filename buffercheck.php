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
 * Performs a buffer test
 *
 * @package    tool_heartbeat
 * @copyright  2017 Rossco Hellmans <rossco@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

$syscontext = context_system::instance();
$PAGE->set_url('/admin/tool/heartbeat/buffercheck.php');
$PAGE->set_context($syscontext);
$PAGE->set_cacheable(false);

$curl = curl_init();
curl_setopt($curl, CURLOPT_HEADER, FALSE);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
curl_setopt($curl, CURLOPT_ENCODING, 'gzip');

$url = new moodle_url('/admin/tool/heartbeat/progress.php');
curl_setopt($curl, CURLOPT_URL, $url);
$response = curl_exec($curl);
$info = curl_getinfo($curl);

$pass = true;

if ($info['http_code'] === 200 && $info['starttransfer_time'] < 1 && $info['total_time'] > 2) {
    echo nl2br("OK: Progress bar is working\n");
} else {
    $pass = false;
    echo nl2br("WARNING: Progress bar is not working\n");
}

$url = new moodle_url('/admin/tool/heartbeat/compresscheck.php');
curl_setopt($curl, CURLOPT_URL, $url);
$response = curl_exec($curl);
$datalength = strlen($response);
$info = curl_getinfo($curl);

if ($info['http_code'] === 200 && $info['size_download'] < $datalength && $info['starttransfer_time'] > 1) {
    echo nl2br("OK: Compression is working\n");
} else {
    $pass = false;
    echo nl2br("WARNING: Compression is not working\n");
}

curl_close($curl);

$files = [
    $CFG->dirroot . '/backup/backup.php',
    $CFG->dirroot . '/backup/restore.php'
];

foreach ($files as $file) {
    $contents = file_get_contents($file);
    $lines = explode("\n", $contents);
    if (in_array("define('NO_OUTPUT_BUFFERING', true);", $lines)) {
        echo nl2br("OK: NO_OUTPUT_BUFFERING exists in $file\n");
    } else {
        $pass = false;
        echo nl2br("WARNING: NO_OUTPUT_BUFFERING is missing in $file\n");
    }
}

$file = $CFG->libdir.'/setuplib.php';
$contents = file_get_contents($file);
$lines = explode("\n", $contents);
if (in_array("    header('X-Accel-Buffering: no');", $lines)) {
    echo nl2br("OK: X-Accel-Buffering: no exists in $file\n");
} else {
    $pass = false;
    echo nl2br("WARNING: X-Accel-Buffering: no is missing in $file\n");
}

if ($pass) {
    exit(0);
} else {
    exit(1);
}