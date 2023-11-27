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
 * @package    core
 * @category   check
 * @copyright  2023 Owen Herbert <owenherbert@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_heartbeat\table;

use core\check\table;
use renderer;

defined('MOODLE_INTERNAL') || die();

/**
 * A table of check results
 *
 * @copyright  2020 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exemption_table extends table {

    /**
     * Render a table of checks
     *
     * @param renderer $output to use
     * @return string html output
     */
    public function render($output) {

        $table = new \html_table();
        $table->data = [];
        $table->head = [
                get_string('status'),
                get_string('check'),
                get_string('summary'),
                'Exemption',
                get_string('action'),
        ];
        $table->colclasses = [
                'rightalign status',
                'leftalign check',
                'leftalign summary',
                'leftalign exemption',
                'leftalign action',
        ];
        $table->id = $this->type . 'reporttable';
        $table->attributes = ['class' => 'admintable ' . $this->type . 'report generaltable'];

        foreach ($this->checks as $check) {
            $ref = $check->get_ref();
            $result = $check->get_result();
            $component = $check->get_component();
            $actionlink = $check->get_action_link();

            $link = new \moodle_url($this->url, ['detail' => $ref]);

            $row = [];
            $row[] = $output->check_result($result);
            $row[] = $output->action_link($link, $check->get_name());

            $row[] = $result->get_summary()
                    . '<br>'
                    . \html_writer::start_tag('small')
                    . $output->action_link($link, get_string('moreinfo'))
                    . \html_writer::end_tag('small');

            $row[] = '';

            if ($actionlink) {
                $row[] = $output->render($actionlink);
            } else {
                $row[] = '';
            }

            $table->data[] = $row;
        }
        $html = \html_writer::table($table);

        if ($this->detail && $result) {
            $html .= $output->heading(get_string('details'), 3);
            $html .= $output->box($result->get_details(), 'generalbox boxwidthnormal boxaligncenter');
            $html .= $output->continue_button($this->url);
        }

        return $html;
    }
}
