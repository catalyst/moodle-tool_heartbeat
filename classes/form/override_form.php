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
 * Override form
 *
 * @package    tool_heartbeat
 * @copyright  2023 Owen Herbert <owenherbert@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace tool_heartbeat\form;

use core\form\persistent;
use tool_heartbeat\object\override;

/**
 * Override form
 *
 * @package    tool_heartbeat
 * @copyright  2023 Owen Herbert <owenherbert@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class override_form extends persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'tool_heartbeat\\object\\override';

    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        // Ref.
        $mform->addElement('static', 'ref', get_string('check'));
        $mform->setConstant('ref', $this->_customdata['ref']);

        // Override.
        $mform->addElement('select', 'override', get_string('override', 'tool_heartbeat'), override::get_status_list());

        // Note.
        $mform->addElement('textarea', 'note', get_string('notes', 'core_notes'), ['rows' => 3]);
        $mform->addRule('note', get_string('noterequired', 'tool_heartbeat'), 'required', null, 'client');

        // URL.
        $mform->addElement('text', 'url', get_string('url'), ['size' => 80]);
        $mform->setType('url', PARAM_URL);
        $urlregex = get_config('tool_heartbeat', 'muteurlregex');
        if (!empty($urlregex)) {
            // Strip backslashes and remove delimiters from the regex display.
            $urlregexdisplay = substr(stripslashes($urlregex), 1, -1);
            $mform->addRule('url', get_string('required'), 'required', null, 'client');
            $mform->addRule('url', get_string('muteurlregex', 'tool_heartbeat', $urlregexdisplay), 'regex', $urlregex, 'client');
            $mform->addElement('static', 'url_help', '', get_string('muteurlregex', 'tool_heartbeat', $urlregexdisplay));
        }

        // Override until.
        $mform->addElement('date_selector', 'expires_at', get_string('expiresat', 'tool_heartbeat'));

        $this->add_action_buttons();
    }
}
