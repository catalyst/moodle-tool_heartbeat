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
 * System Status report
 *
 * @package    tool_heartbeat
 * @copyright  2023 Owen Herbert (owenherbert@catalyst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_heartbeat\form\override_form;
use tool_heartbeat\object\override;

define('NO_OUTPUT_BUFFERING', true);

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_heartbeat_status');

// Check if an ID is provided.
$ref = required_param('ref', PARAM_TEXT);
$unmute = optional_param('unmute', null, PARAM_BOOL);

// Set the PAGE URL (and mandatory context). Note the ID being recorded, this is important.
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/admin/tool/heartbeat/override.php', ['ref' => $ref]));

// The URL used for redirection.
$statuspage = new moodle_url('/admin/tool/heartbeat/status.php');

// Attempt to instantiate a persistent object.
$override = override::get_active_override($ref);

// Handle unmute.
if (!empty($unmute) && !empty($override) && $unmute) {
    try {
        $override->resolve();
        \core\notification::success(get_string('changessaved'));
    } catch (Exception $e) {
        \core\notification::error($e->getMessage());
    }
    redirect($statuspage);
}

// Try restoring override.
$restored = false;
if (empty($override)) {
    $override = override::get_restored_override($ref);
    $restored = !empty($override);
}

// Create the form instance. We need to use the current URL and the custom data.
$customdata = [
    'persistent' => $override,
    'ref' => $ref,
];

// Create override form.
$form = new override_form($PAGE->url->out(false), $customdata);

// Get the data. This ensures that the form was validated.
if ($form->is_cancelled()) {
    redirect($statuspage);
} else if ($data = $form->get_data()) {

    try {
        if (empty($data->id)) {
            // If there is no ID we need to create a new persistent.
            $data->userid = $USER->id;
            $override = new override(0, $data);
            $override->create();
        } else {
            // If we have an ID we need to update the persistent.
            $override->from_record($data);
            $override->update();
        }
        \core\notification::success(get_string('changessaved'));
    } catch (Exception $e) {
        \core\notification::error($e->getMessage());
    }

    // Redirect to heartbeat status page when done.
    redirect($statuspage);
}

if ($restored) {
    \core\notification::INFO(get_string('overriderestore', 'tool_heartbeat'));
}

// Display the mandatory header and footer.
// And display the form, and its validation errors if there are any.
echo $OUTPUT->header();
$headingstring = (empty($override) || $restored) ? 'addmute' : 'editmute';
echo $OUTPUT->heading(get_string($headingstring, 'tool_heartbeat'));
$form->display();
echo $OUTPUT->footer();
