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

/**
 * Range request check class.
 *
 * @copyright  2022
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rangerequestcheck extends check {
    /** @var string Range header value requested. */
    const RANGE = '0-9';

    /** @var int Number of body bytes expected back. */
    const EXPECTED_BYTES = 10;

    /**
     * URLs to test for range request support. Add more here.
     *
     * @return \moodle_url[]
     */
    protected function get_urls(): array {
        return [
            new \moodle_url('/pluginfile.php/1/tool_heartbeat/test'),
        ];
    }

    /**
     * Range request check provider.
     *
     * @return result
     */
    public function get_result(): result {
        $failures = [];
        $table = new \html_table();
        $table->head = ['URL', 'Sent', 'Expected HTTP code', 'Expected body', 'Got'];
        $table->attributes['class'] = 'generaltable';

        foreach ($this->get_urls() as $url) {
            [$info, $row] = $this->check_url($url);
            $table->data[] = $row;
            if (!$info['ok']) {
                $failures[] = $info;
            }
        }

        $details = \html_writer::table($table);

        if (!$failures) {
            return new result(result::OK, get_string('checkrangerequestok', 'tool_heartbeat'), $details);
        }

        $first = $failures[0];
        return new result(result::ERROR, get_string('checkrangerequestbad', 'tool_heartbeat', [
            'url'   => $first['url'],
            'code'  => $first['code'],
            'bytes' => $first['bytes'],
        ]), $details);
    }

    /**
     * Send a range request to one URL.
     *
     * @param \moodle_url $url
     * @return array [summary info array with ok/url/code/bytes, table row cells]
     */
    protected function check_url(\moodle_url $url): array {
        global $CFG;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url->out(false));
        curl_setopt($curl, CURLOPT_RANGE, self::RANGE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        // Dev boxes commonly have insecure certs; certificate problems are covered by other checks.
        if ($CFG->debug >= DEBUG_DEVELOPER) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        $error = curl_error($curl);
        curl_close($curl);

        $ok = $response !== false && $info['http_code'] === 206 && $info['size_download'] == self::EXPECTED_BYTES;

        $got = '';
        if (!$ok) {
            $headers = $response !== false ? trim(substr($response, 0, $info['header_size'])) : '';
            $got = \html_writer::tag('pre', s($error !== '' ? $error : $headers) .
                "\nBody bytes: " . s($info['size_download']));
        }

        $row = [
            \html_writer::link($url, s($url->out_as_local_url(false))),
            'Range: bytes=' . self::RANGE,
            '206',
            self::EXPECTED_BYTES . ' bytes',
            $got,
        ];

        return [[
            'ok'    => $ok,
            'url'   => $url->out(false),
            'code'  => $info['http_code'],
            'bytes' => $info['size_download'],
        ], $row];
    }
}
