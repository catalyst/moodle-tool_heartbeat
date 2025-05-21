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
 * A table of check results
 *
 * @package    tool_heartbeat
 * @copyright  2023 Owen Herbert <owenherbert@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_heartbeat\table;

use core\check\table;
use core\check\result;
use html_writer;
use renderer;
use tool_heartbeat\checker;

/**
 * A table of check results
 *
 * @copyright  2020 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class status_table extends table {

    /**
     * Render a table of checks
     *
     * @param renderer $output to use
     * @return string html output
     */
    public function render($output) {

        $html = '';

        $table = new \html_table();
        $table->data = [];
        $table->head = [
            get_string('status'),
            get_string('check'),
            get_string('summary'),
            get_string('action'),
            get_string('mute', 'tool_heartbeat'),
        ];
        $table->colclasses = [
            'rightalign status',
            'leftalign check',
            'leftalign summary',
            'leftalign action',
            'leftalign mute',
        ];
        $table->id = $this->type . 'reporttable';
        $table->attributes = ['class' => 'admintable ' . $this->type . 'report generaltable'];

        $checks = checker::remove_supressed_checks($this->checks);
        foreach ($checks as $check) {
            $ref = $check->get_ref();
            $result = checker::get_overridden_result($check);
            $actionlink = $check->get_action_link();
            $reportlink = new \moodle_url('/report/status/index.php', ['detail' => $ref]);

            $row = [];
            $row[] = $output->check_result($result);
            $row[] = $output->action_link($reportlink, $check->get_name());

            $row[] = $result->get_summary()
                . '<br>'
                . html_writer::start_tag('small')
                . $output->action_link($reportlink, get_string('moreinfo'))
                . html_writer::end_tag('small');

            if ($actionlink) {
                $row[] = $output->render($actionlink);
            } else {
                $row[] = '';
            }
            $row[] = $this->get_override_html($output, $ref, $result);
            $table->data[] = $row;
        }
        $html .= html_writer::table($table);

        return $html;
    }

    /**
     * Returns the html output for the override column.
     *
     * @param renderer $output
     * @param string $ref
     * @param result $result
     * @return string html output
     */
    private function get_override_html($output, string $ref, result $result): string {
        $override = \tool_heartbeat\object\override::get_active_override($ref);
        $overridelink = new \moodle_url('/admin/tool/heartbeat/override.php', ['ref' => $ref]);
        $rowdata = '';

        // If we have an existing override, display a link to edit and delete.
        if (isset($override)) {
            $dellink = new \moodle_url('/admin/tool/heartbeat/override.php', [
                'ref' => $ref,
                'unmute' => true,
            ]);

            $notes = $override->get('note');
            $url = $override->get('url');

            $rowdata .= get_string('expiresat', 'tool_heartbeat') . ': ' . $override->get_time_until_mute_ends();
            $rowdata .= format_text($notes);
            $rowdata .= !empty($url) ? html_writer::link($url, $url) . '<br>' : '';
            $rowdata .= html_writer::start_tag('small');
            $rowdata .= $output->action_link($overridelink, get_string('edit'));
            $rowdata .= ' | ';
            $rowdata .= $output->action_link($dellink, get_string('unmute', 'tool_heartbeat'));
            $rowdata .= html_writer::end_tag('small');
            return $rowdata;
        }

        // If the status of a check isn't normal, display a link to create an override.
        $overridablestatus = [result::CRITICAL, result::WARNING, result::ERROR, result::UNKNOWN];
        if (in_array($result->get_status(), $overridablestatus)) {
            $rowdata .= $output->action_link($overridelink, get_string('addmute', 'tool_heartbeat'));
            return $rowdata;
        }

        return $rowdata;
    }
}
