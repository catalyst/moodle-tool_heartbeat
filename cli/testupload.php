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
 * Upload speed check
 *
 * @package    tool_heartbeat
 * @copyright  2017 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This is a cli script which then calls an upload handler.
 *
 */

define('NO_UPGRADE_CHECK', true);
define('CLI_SCRIPT', true);

require('../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    array(
        'help'    => false,
        'size'    => 1024 * 10,
        'chunks'  => 100,
        'delay'   => 0,
        'wwwroot' => $CFG->wwwroot,
        'verbose' => 0,
    ),
    array(
        'h'   => 'help',
        's'   => 'size',
        'c'   => 'chunks',
        'd'   => 'delay',
        'w'   => 'wwwroot',
        'v'   => 'verbose',
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
  -h, --help     Print out this help
  -s, --size     Size in bytes to upload (10kB)
  -c, --chunks   Number of chunks to send (10)
  -d, --delay    Delay in ms between each chunk (0ms)
  -w, --wwwroot  URL base, can be used to test a different moodle (0ms)
  -v, --verbose  URL base, can be used to test a different moodle (0ms)

Example:
\$sudo -u www-data /usr/bin/php admin/tool/heartbeat/cli/testupload.php
";
        die;
}

$url = $options['wwwroot'] . '/admin/tool/heartbeat/upload.php';

$parts = parse_url($url);
$host = $parts['host'];
$path = $parts['path'];
$port = $parts['scheme'] == 'https' ? 443 : 80;
if ($port == '443') {
    $host = "ssl://".$host;
}

$sock = fsockopen($host, $port, $errno, $errstr, 30);
if (!$sock) {
    die("$errstr ($errno)\n");
}

$chunks = $options['chunks'];
$data = random_bytes($options['size']);
$contentlength = $chunks * strlen($data);

$request = "PUT $path HTTP/1.0\r\n".
"Host: $host\r\n".
"Content-type: text/plain\r\n".
"Content-length: $contentlength\r\n".
"Accept: */*\r\n".
"\r\n";

fwrite($sock, $request);
if ($options['verbose']) {
    echo "-- REQUEST ----------------------------------------------------------------\n";
    print $request;
    echo "-- UPLOAD -----------------------------------------------------------------\n";
}

// Push data in.
for ($c = 0; $c < $chunks; $c++) {
    fwrite($sock, $data);
    fflush($sock);
    if ($options['verbose']) {
        print ".";
    }
    usleep( $options['delay'] * 1000 );
}

// Read data back.
$headers = "";
while ($str = trim(fgets($sock, 4096))) {
    $headers .= "$str\n";
}

$body = "";
while (!feof($sock)) {
    $body .= fgets($sock, 4096);
}

fclose($sock);

if ($options['verbose']) {
    echo "\n";
    echo "-- RESPONSE HEADER --------------------------------------------------------\n";
    echo $headers;
    echo "-- RESPONSE BODY ----------------------------------------------------------\n";
}
echo $body;
echo "\n";
if ($options['verbose']) {
    echo "-- CLOSE ------------------------------------------------------------------\n";
}

