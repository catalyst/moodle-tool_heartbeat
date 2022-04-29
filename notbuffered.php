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
 * Performs a progress bar test
 *
 * @package    tool_heartbeat
 * @copyright  2017 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is a test of a "normal" moodle file which does NOT
// set define('NO_OUTPUT_BUFFERING', true); which means that:
// 1) php will buffer into its own ob buffers
// 2) php-fpm will buffer / or apache will buffer
// 3) somewhere the compression libs will also buffer
// 4) varnish might do something dumb too
//
// This situation is very well tested for the case where buffering
// is OFF, but very opaque for whn stuff will hit the network for
// the case when buffering is on.
//

require(__DIR__ . '/../../../config.php');

tool_heartbeat\lib::validate_ip_against_config();

// Right now we have a session lock, but we ourselves have not sent anything over the network.

// We can set a header, but header.
header('Content-Type:text/html');

for ($c = 1; $c <= 10; $c++) {
    $bytes = 100;

    echo "c = $c\n";

    // Sleep my pretty.
    usleep(20 * 1000);
}


