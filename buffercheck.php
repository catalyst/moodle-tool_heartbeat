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

define('NO_MOODLE_COOKIES', true);
require(__DIR__ . '/../../../config.php');

header("Content-Type: text/plain");

// Make sure varnish doesn't cache this. But it still might so go check it!
header('Pragma: no-cache');
header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
header('Expires: Tue, 04 Sep 2012 05:32:29 GMT');

$curl = curl_init();
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
curl_setopt($curl, CURLOPT_ENCODING, 'gzip');

$pass = true;

$output = '';

$url = new moodle_url('/admin/tool/heartbeat/compresscheck.php');
curl_setopt($curl, CURLOPT_URL, $url);
$response = curl_exec($curl);
$datalength = strlen($response);
$info = curl_getinfo($curl);

if ($response !== false) {
    if ($info['http_code'] === 200 && $info['size_download'] < $datalength && $info['starttransfer_time'] > 1) {
        $output .= "OK: Compression is working\n";
    } else {
        $pass = false;
        $output .= "WARNING: Compression is not working\n";
    }
} else {
    $pass = false;
    $output .= "WARNING: Compression is not working\n";

    $err = curl_error($curl);
    $errno = curl_errno($curl);

    if ($err) {
        $output .= "Curl failed with the following error: ($errno) $err";
    } else {
        $output .= "Curl failed and no error was returned";
    }
}

$files = [
    $CFG->dirroot . '/backup/backup.php',
    $CFG->dirroot . '/backup/restore.php'
];

foreach ($files as $file) {
    $contents = file_get_contents($file);
    $lines = explode("\n", $contents);
    if (in_array("define('NO_OUTPUT_BUFFERING', true);", $lines)) {
        $output .= "OK: NO_OUTPUT_BUFFERING exists in $file\n";
    } else {
        $pass = false;
        $output .= "WARNING: NO_OUTPUT_BUFFERING is missing in $file\n";
    }
}

$file = $CFG->libdir.'/setuplib.php';
$contents = file_get_contents($file);
$lines = explode("\n", $contents);
if (in_array("    header('X-Accel-Buffering: no');", $lines)) {
    $output .= "OK: X-Accel-Buffering: exists in $file\n";
} else {
    $pass = false;
    $output .= "WARNING: X-Accel-Buffering: is missing in $file\n";
}

$url = new moodle_url('/admin/tool/heartbeat/progress.php');
// Use http://404.php.net/ to fake a curl domain error.
// Use http://blackhole.webpagetest.org/ to fake a curl timeout error.
curl_setopt($curl, CURLOPT_URL, $url);
$response = curl_exec($curl);
$info     = curl_getinfo($curl);

if ($response !== false) {
    if ($info['http_code'] === 200 && $info['starttransfer_time'] < 2 && $info['total_time'] > 8) {
        $output .= "OK: Progress bar is working";
    } else {
        $pass = false;
        $output .= "WARNING: Progress bar is not working";
    }

    $output .= " Debugging:: URL: {$info['url']}";
    $output .= " HTTP code: {$info['http_code']}";
    $output .= " Total time: {$info['total_time']}"
    $output .= " TTFB: {$info['starttransfer_time']}\n";
} else {
    $pass = false;
    $output .= "WARNING: Progress bar is not working\n";

    $err = curl_error($curl);
    $errno = curl_errno($curl);

    if ($err) {
        $output .= "Curl failed with the following error: ($errno) $err";
    } else {
        $output .= "Curl failed and no error was returned";
    }
}

curl_close($curl);

if ($pass) {
    echo "OK: All tests passed\n";
    echo $output;
    exit(0);
} else {
    echo "Fail: not all tests passed\n";
    echo $output;
    exit(1);
}

