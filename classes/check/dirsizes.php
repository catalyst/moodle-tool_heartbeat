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
 * @package    tool_heartbeat
 * @copyright  2023 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace tool_heartbeat\check;
use core\check\check;
use core\check\result;

/**
 * Dir sizes performance check.
 *
 * @copyright  2023
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dirsizes extends check {

    /**
     * Get Result.
     *
     * @return result
     */
    public function get_result(): result {
        global $CFG;

        $sizedataroot = $this->dirsize_totara('dataroot');
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
     * @param string $cfg the path to check
     * @return string $size for a path as html
     */
    private function dirsize(string $cfg) {
        global $CFG;
        if (!property_exists($CFG, $cfg)) {
            return "<br>\$CFG->$cfg not in use";
        }
        $path = $CFG->{$cfg};
        $size = get_directory_size($path);

        return "<br>\$CFG->{$cfg} = " . display_size($size);
    }

    /**
     * Get a path's size (compatible with Totara)
     * @param string $cfg the path to check
     * @return int $size size for a path as an integer
     */
    private function dirsize_totara(string $cfg): int {
        global $CFG;
        if (!property_exists($CFG, $cfg)) {
            return 0;
        }
        $rootdir = $CFG->{$cfg};

        if (!is_dir($rootdir)) {
            // Must be a directory.
            return 0;
        }

        if (!$dir = @opendir($rootdir)) {
            // Can't open it for some reason.
            return 0;
        }

        $size = 0;

        while (false !== ($file = readdir($dir))) {
            $firstchar = substr($file, 0, 1);
            if ($firstchar == '.' or $file == 'CVS') {
                continue;
            }
            $fullfile = $rootdir .'/'. $file;
            
            $filesize = filesize($fullfile);
            if ($filesize === false) {
                continue;
            }   
            
            $size += $filesize;
        }
        closedir($dir);

        return $size;
    }

}
