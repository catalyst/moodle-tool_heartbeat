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
 * Auth method health check.
 *
 * @package    tool_heartbeat
 * @author     Srdjan Janković <srdjan@catalyst.net.nz>
 * @copyright  Catalyst IT
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

use tool_heartbeat\logger;
use Exception;

defined('MOODLE_INTERNAL') || die();

class logcheck extends check {

    public function get_action_link(): ?\action_link {
        $url = new \moodle_url('/admin/tool/heartbeat/settings.php', ['section' => 'log']);
        return new \action_link($url, get_string('logstream', 'tool_heartbeat'));
    }

    public function get_result() : result {
        if ($logstream = get_config('tool_heartbeat', 'logstream')) {
            try {
                logger::log_to_stream(, ['check']);
                $status = result::OK;
                $summary = '';
            } catch (Exception $e) {
                $status = result::WARNING;
                $summary = get_string('logstreamerror', 'tool_heartbeat', "$logstream ".$e->getMessage());
            }
        } else {
            $status = result::WARNING;
            $summary = get_string('logstreamnotset', 'tool_heartbeat');
        }

        return new result($status, $summary);
    }
}
