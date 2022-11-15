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

namespace tool_heartbeat\check;
use core\check\check;
use core\check\result;

/**
 * Logstore check.
 *
 * @package   tool_heartbeat
 * @author    Kevin Pham <kevinpham@catalyst-au.net>
 * @copyright Catalyst IT, 2022
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logstorecheck extends check {

    /**
     * A link to configure logging
     *
     * @return action_link|null
     */
    public function get_action_link(): ?\action_link {
        $url = new \moodle_url('/admin/settings.php', ['section' => 'managelogging']);
        return new \action_link($url, get_string('managelogging', 'tool_log'));
    }

    /**
     * Process and return whether there is one or more logstores
     *
     * @return result of the check
     */
    public function get_result(): result {
        // Define possible responses.
        $ok = new result(result::OK, get_string('checklogstoreok', 'tool_heartbeat'));
        $bad = new result(result::CRITICAL, get_string('checklogstorebad', 'tool_heartbeat'));

        $enabledstores = get_config('tool_log', 'enabled_stores');
        if (empty($enabledstores)) {
            // No logstores are defined (null), or are all disabled ("").
            return $bad;
        }

        return $ok;
    }
}
