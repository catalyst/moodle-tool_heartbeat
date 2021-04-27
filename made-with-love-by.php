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
 * Made with love <3
 *
 * @package    tool_heartbeat
 * @copyright  2021 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

// @codingStandardsIgnoreStart
require_once('../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once(__DIR__.'/iplock.php');

$ascii = gzuncompress(base64_decode('eJy1VNsJgDAM/HeKzioiThCF4nKdRNESm+aSVtCQr0vsPVoM4bcaMJxoObsPhcNE6w1cHTM2MvYnM9NumXNKNBcw80uR5RDCiUhNzNVyW0h8oE7y2lXkLIWv7Iidg4B0ivUE6tTp1lk7QUtl+Hlo8+5SW/HeuA6pPsI8QaqkQnBU9KXrXVCO1klWaISm7Tj0xF6tXDSXVG/GZ8E9Tp1h/DO84kfUt/6KwdX6OdvLOgC47Z0k'));
// @codingStandardsIgnoreEnd

if (stristr($_SERVER["HTTP_USER_AGENT"], 'curl')) {
    for ($c = 0; $c < 10; $c++) {
        print ascii2cli($ascii, 2);
        usleep(800000);
        print ascii2cli($ascii, 3);
        usleep(50000);
        print ascii2cli($ascii, 4);
        usleep(50000);
        print ascii2cli($ascii, 5);
        usleep(100000);
        print ascii2cli($ascii, 4);
        usleep(100000);
        print ascii2cli($ascii, 3);
        usleep(100000);
    }
    print str_repeat("\n", 14);
    exit;
}

echo <<<EOF
<style>
body {
    background: #000;
}
.terminal {
    background: #000;
    color: #aaa;
    font-family: Monaco, monospace;
    font-weight: 400;
    font-smooth: never;
    font-size: 13px;
    padding: 26px;
    margin: 1em;
    color: #888;
    width: 640px;
}
.terminal::before {
    content: '$ ';
}
.bell {
  background: #888;
  animation: flash 1s infinite;
}
@keyframes flash {
  0%   { visibility: visible;}
  49%  { visibility: visible;}
  50%  { visibility: hidden; }
  99%  { visibility: hidden; }
  100% { visibility: visible;}
}
</style>
EOF;

print <<<EOT
<pre class=terminal>
curl {$CFG->wwwroot}/admin/tool/heartbeat/made-with-love-by.php<span class=bell> </span>
</pre>
EOT;

exit;

/**
 * Render as ascii art in cli
 *
 * @param string  $text to show
 * @param integer $animate frame position
 * @return string cli
 */
function ascii2cli($text, $animate = 0) {
    $bnw    = "\033[47m\033[30;1m";
    $red    = "\033[31m";

    $cycle  = $animate * 36 + 16; // Red spectrum.
    $red    = "\033[38;5;$cycle".'m';

    $reset  = "\033[0;0m";

    $lines = explode("\n", $text);
    $text = "\n             The Heartbeat plugin was proudly made with <3 by Catalyst IT\n\n";
    foreach ($lines as $line) {
        $word = mb_substr($line, 0, 73);
        $logo = mb_substr($line, 73);
        $text .= "$bnw$word$red$logo$reset\n";
    }
    $text .= str_repeat(cli_ansi_format('<cursor:up>'), 14);
    return $text;
}
