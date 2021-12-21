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
 * MFA management class.
 *
 * @package     tool_heartbeat
 * @author      Srdjan Janković <srdjan@catalyst.net.nz>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_heartbeat\wrapper;

use moodle_exception, Exception;

defined('MOODLE_INTERNAL') || die();

class syslog {
    private $facility;

    public static function syslog_facility($facility = null): ?int {
        if ($facility == 'user') {
            return LOG_USER;
        } elseif (preg_match('/^local([0-7])$/', $facility, $matches)) {
            return LOG_LOCAL0 + (8 * (int) $matches[1]);
        }

        return null;
    }

    public function stream_open($path, $mode, $options, &$opened_path): bool {
        global $SITE;

        $url = parse_url($path);
        if (!$url) {
            throw new moodle_exception("tool_heartbeat\\wrapper\\syslog: Invalid syslog url $path");
        }

        $this->facility = strtolower($url['host']);
        $prefix = isset($url['path']) ? trim($url['path'], '/') : null;
        if (empty($prefix)) {
            $prefix = $SITE->shortname;
        }

        if ($f = self::syslog_facility($this->facility)) {
            openlog($prefix, LOG_NDELAY | LOG_PID, $f);
        }
        else {
            throw new moodle_exception("tool_heartbeat\\wrapper\\syslog: Invalid syslog facility {$this->facility}");
        }

        return true;
    }

    public function stream_write($data): int {
        if (syslog(LOG_INFO, $data)) {
            return strlen($data);
        }
        throw new moodle_exception("tool_heartbeat\\wrapper\\syslog: Cannot log to {$this->facility}");
    }

    public function stream_eof(): bool {
        return true;
    }

    public function stream_close(): void {
        closelog();
    }
}
