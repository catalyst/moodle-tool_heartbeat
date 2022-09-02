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
 *  Heartbeat tool plugin settings
 *
 * @package    tool_heartbeat
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $settings = new admin_settingpage('tool_heartbeat', get_string('pluginname', 'tool_heartbeat'));

    $ADMIN->add('tools', $settings);
    if (!during_initial_install()) {

        $options = array(
            '' => new lang_string('normal', 'tool_heartbeat'),
            'warn' => new lang_string('testwarning', 'tool_heartbeat'),
            'error' => new lang_string('testerror', 'tool_heartbeat'),
        );
        $settings->add(new admin_setting_configselect('tool_heartbeat/testing',
                        new lang_string('testing',        'tool_heartbeat'),
                        new lang_string('testingdesc',    'tool_heartbeat'),
                        'error',
                        $options));

        // Current IP validation against list for description.
        $allowedips = tool_heartbeat\lib::get_allowed_ips();
        $description = '';
        if (trim($allowedips) == '') {
            $message = 'allowedipsempty';
            $type = 'notifymessage';
        } else if (remoteip_in_list($allowedips)) {
            $message = 'allowedipshasmyip';
            $type = 'notifysuccess';
        } else {
            $message = 'allowedipshasntmyip';
            $type = 'notifyerror';
        };
        $description .= $OUTPUT->notification(get_string($message, 'tool_heartbeat', ['ip' => getremoteaddr()]), $type);
        $description .= html_writer::tag('p', get_string('ips_combine', 'tool_heartbeat'));

        // IP entry box for blocking.
        $iplist = new admin_setting_configiplist('tool_heartbeat/allowedips',
                    new lang_string('allowedipstitle', 'tool_heartbeat'),
                    (new lang_string('allowedipsdescription', 'tool_heartbeat').$description),
                    ''  );
        $settings->add($iplist);

        $iplist = new admin_setting_configiplist(
            'tool_heartbeat/allowedips_forced',
            get_string('builtinallowediplist', 'tool_heartbeat'),
            get_string('builtinallowediplist_desc', 'tool_heartbeat'),
            ''
        );
        $settings->add($iplist);

        $settings->add(new admin_setting_configduration('tool_heartbeat/errorlog',
                get_string('errorlog', 'tool_heartbeat'),
                get_string('errorlogdesc', 'tool_heartbeat'), 30 * MINSECS, MINSECS));

        $settings->add(new admin_setting_configtext('tool_heartbeat/configuredauths',
                get_string('configuredauths', 'tool_heartbeat'),
                get_string('configuredauthsdesc', 'tool_heartbeat'), '', PARAM_TEXT));

        $opts = [
            'critical' => 'CRITICAL',
            'criticalbusiness' => get_string('error_critical_business', 'tool_heartbeat'),
            'warning' => 'WARNING'
        ];
        $time = new \DateTime('now', core_date::get_server_timezone_object());
        $settings->add(new admin_setting_configselect('tool_heartbeat/errorcritical',
                get_string('errorascritical', 'tool_heartbeat'),
                get_string('errorascritical_desc', 'tool_heartbeat', $time->format('e P')), 'warning', $opts));

        $example = '\logstore_standard\task\cleanup_task, 5, 5, 5';
        $settings->add(new admin_setting_configtextarea('tool_heartbeat/tasklatencymonitoring',
                get_string('tasklatencymonitoring', 'tool_heartbeat'),
                get_string('tasklatencymonitoring_desc', 'tool_heartbeat', $example), '', PARAM_TEXT));
    }
}
