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

namespace tool_heartbeat;

use core_shutdown_manager;
use moodle_exception, Exception;

defined('MOODLE_INTERNAL') || die();

class logger {
    private static $classname = __NAMESPACE__.'\logger';
    protected static $statcallbacks = [];

    /**
     * Register shutdown handler
     *
     * @return void
     */
    public static function register_shutdown_handler(): void {
        core_shutdown_manager::register_function([self::$classname, 'log']);
    }

    /**
     * Register log callback
     *
     * @param callable $cb
     * @param ?string  $stream A fopen() compliant string
     * @return void
     */
    public static function register_stat_callback(callable $cb, $stream = null): void {
        if (!isset(self::$statcallbacks[$stream])) {
            self::$statcallbacks[$stream] = [];
        }
        self::$statcallbacks[$stream][] = $cb;
    }

    /**
     * Shutdown handler
     *
     * @return void
     */
    public static function log(): void {
        $defaultstream = self::default_log_stream();
        if ($defaultstream) {
            self::$statcallbacks[$defaultstream] = self::$statcallbacks[null];
            self::$statcallbacks[$defaultstream][] = [self::$classname, 'default_stats'];
        }
        unset(self::$statcallbacks[null]);


        foreach(self::$statcallbacks as $stream => $callbacks) {
            $lines = [];
            foreach ($callbacks as $cb) {
                try {
                    $lines = array_merge($lines, call_user_func($cb));
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }
            if ($lines) {
                try {
                    self::log_to_stream($stream, $lines);
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }
        }
    }


    /**
     * Log lines to a stream
     *
     * @param string $stream A fopen() compliant string
     * @param array $lines
     * @return void
     * @throws moodle_exception
     */
    public static function log_to_stream($stream, array $lines): void {
        if ($fh = fopen($stream, 'a')) {
            foreach ($lines as $l) {
                if (fwrite($fh, $l) === false) {
                    throw new moodle_exception("tool_heartbeat\\logger::log_to_stream: Cannot write to $stream");
                }
            }
        } else {
            throw new moodle_exception("tool_heartbeat\\logger::log_to_stream: Cannot open $stream");
        }
    }

    /**
     * Get default log stream
     *
     * @return ?string
     */
    private static function default_log_stream(): ?string {
        if(PHPUNIT_TEST) {
            return null;
        }

        return '';
    }

    /**
     * Stats collector
     *
     * @return array
     */
    public static function default_stats(): array {
        global $CFG, $PERF, $DB, $PAGE;

        $stats = [
            'db_reads: '.$DB->perf_get_reads(),
            'db_writes: '.$DB->perf_get_writes(),
        ];
        if (function_exists('memory_get_peak_usage')) {
            $stats[] = 'memory_peak: '.memory_get_peak_usage();
        }

        return $stats;
    }
}

