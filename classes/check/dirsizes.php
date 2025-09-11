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
 * Dir sizes performance check.
 *
 * @package   tool_heartbeat
 * @copyright 2023 Brendan Heywood <brendan@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_heartbeat\check;
use core\check\check;
use core\check\result;

/**
 * Dir sizes performance check.
 *
 * @copyright 2023
 * @author    Brendan Heywood <brendan@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dirsizes extends check {

    /**
     * Get Result.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        $sizedataroot = $this->dirsize('dataroot', true);
        $summary = $sizedataroot;
        $details = "Shared paths:<br>";
        $details .= '$CFG->dataroot = ' . display_size($sizedataroot);
        $details .= $this->dirsize('themedir');
        $details .= $this->dirsize('tempdir');
        $details .= $this->dirsize('cachedir');

        $host = gethostname();
        $details .= "<br><br>Optionally local paths (Host: $host)\n";
        $details .= $this->dirsize('localcachedir');
        $details .= $this->dirsize('localrequestdir');

        return new result(result::INFO, $summary, $details);
    }
    /**
     * Get a path's size
     *
     * @param  string $cfg the path to check
     * @param  bool $rawsize return rawsize of directory
     * @return string $size for a path as html
     */
    private function dirsize(string $cfg, bool $rawsize = false) {
        global $CFG;
        if (!property_exists($CFG, $cfg)) {
            return "<br>\$CFG->$cfg not in use";
        }
        $path = $CFG->{$cfg};

        // If Totara, use Totara-compatible function.
        if (!empty($CFG->totara_version)) {
            $size = $this->get_directory_size_totara($path);
        } else {
            $size = get_directory_size($path);
        }

        if ($rawsize) {
            return $size;
        }

        return "<br>\$CFG->{$cfg} = " . display_size($size);
    }

    /**
     * Recursively calculate the size of a directory (Totara-compatible).
     *
     * This replicates Moodle core's get_directory_size() logic,
     * but avoids using it directly for Totara compatibility.
     *
     * @param  string $dir The directory path to measure
     * @return int Total size in bytes
     */
    private function get_directory_size_totara(string $dir): int {
        if (!is_dir($dir)) {
            return 0;
        }

        $size = 0;
        if (!$dh = @opendir($dir)) {
            return 0;
        }

        while (false !== ($file = readdir($dh))) {
            // Skip hidden files and CVS dirs.
            if ($file[0] === '.' || $file === 'CVS') {
                continue;
            }

            $fullfile = $dir . '/' . $file;

            if (is_dir($fullfile)) {
                // Recurse into subdirectory.
                $size += $this->get_directory_size_totara($fullfile);
            } else {
                $filesize = filesize($fullfile);
                if ($filesize !== false) {
                    $size += $filesize;
                }
            }
        }

        closedir($dh);
        return $size;
    }
}
