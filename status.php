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

use tool_heartbeat\table\status_table;

define('NO_OUTPUT_BUFFERING', true);

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_heartbeat_status', '', null, '', ['pagelayout' => 'report']);

$url = '/admin/tool/heartbeat/status.php';
$table = new status_table('status', $url);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_status'));
echo $table->render($OUTPUT);
echo $OUTPUT->footer();
