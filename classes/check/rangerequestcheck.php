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
 * Range request performance check.
 *
 * @package    tool_heartbeat
 * @copyright  2022
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This can be run either as a web api, or on the CLI. When run on the
 * CLI it conforms to the Nagios plugin standard.
 *
 * See also:
 *  - http://nagios.sourceforge.net/docs/3_0/pluginapi.html
 *  - https://nagios-plugins.org/doc/guidelines.html#PLUGOUTPUT
 */

namespace tool_heartbeat\check;
use core\check\check;
use core\check\result;

defined('MOODLE_INTERNAL') || die();

class rangerequestcheck extends check {

    public function get_result() : result {
        global $DB, $CFG;

        $url = $CFG->wwwroot . '/pluginfile.php/1/tool_heartbeat/test';

        $curl = new \curl();
        $curl->setopt([
            'CURLOPT_RANGE' => '0-9',
        ]);

        $response = $curl->get($url);
        $datalength = strlen($response);
        $info = $curl->get_info();

        if (empty($info)) {
            // Happens when the url is blocked.
            return new result(result::ERROR, get_string('checkrangerequestfailed', 'tool_heartbeat',
                ['error' => $response]));
        }

        if ($response !== false) {
            if ($info['http_code'] === 206 && $info['size_download'] == 10) {
                return new result(result::OK, get_string('checkrangerequestok', 'tool_heartbeat'));
            }
        }

        return new result(result::ERROR, get_string('checkrangerequestbad', 'tool_heartbeat', [
            'url'   => $url,
            'code'  => $info['http_code'],
            'bytes' => $info['size_download'],
        ]));
    }
}
