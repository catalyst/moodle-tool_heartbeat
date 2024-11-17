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

    $category = new admin_category('heartbeatfolder', get_string('pluginname', 'tool_heartbeat'));
    $ADMIN->add('tools', $category);

    $statuspage = new admin_externalpage(
        'tool_heartbeat_status',
        get_string('systemstatus', 'tool_heartbeat'),
        new moodle_url('/admin/tool/heartbeat/status.php')
    );
    $ADMIN->add('reports', $statuspage);

    $settings = new admin_settingpage('tool_heartbeat', get_string('settings'));
    $ADMIN->add('heartbeatfolder', $settings);

    if (!during_initial_install()) {

        $options = [
            '' => new lang_string('normal', 'tool_heartbeat'),
            'warn' => new lang_string('testwarning', 'tool_heartbeat'),
            'error' => new lang_string('testerror', 'tool_heartbeat'),
        ];
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
            'warning' => 'WARNING',
        ];
        $time = new \DateTime('now', core_date::get_server_timezone_object());
        $settings->add(new admin_setting_configselect('tool_heartbeat/errorcritical',
                get_string('errorascritical', 'tool_heartbeat'),
                get_string('errorascritical_desc', 'tool_heartbeat', $time->format('e P')), 'warning', $opts));

        $settings->add(new admin_setting_configduration('tool_heartbeat/mutedefault',
            get_string('settings:mutedefault', 'tool_heartbeat'),
            get_string('settings:mutedefault:desc', 'tool_heartbeat'), 12 * WEEKSECS, WEEKSECS));

        $statuslist = tool_heartbeat\object\override::get_status_list();
        reset($statuslist);
        $settings->add(new admin_setting_configselect('tool_heartbeat/mutedefaultstatus',
            get_string('settings:mutedefaultstatus', 'tool_heartbeat'),
            get_string('settings:mutedefaultstatus:desc', 'tool_heartbeat'), key($statuslist), $statuslist));

        $settings->add(new admin_setting_configtext('tool_heartbeat/muteurlregex',
            get_string('settings:muteurlregex', 'tool_heartbeat'),
            get_string('settings:muteurlregex:desc', 'tool_heartbeat'), '', PARAM_TEXT));

        $example = '\logstore_standard\task\cleanup_task, 5, 5, 5';
        $settings->add(new admin_setting_configtextarea('tool_heartbeat/tasklatencymonitoring',
                get_string('tasklatencymonitoring', 'tool_heartbeat'),
                get_string('tasklatencymonitoring_desc', 'tool_heartbeat', $example), '', PARAM_TEXT));

        // Cache consistency check settings.
        $settings->add(new admin_setting_heading('tool_heartbeat/cachechecksettings',
            get_string('settings:cachecheckheading', 'tool_heartbeat'),
            ''
        ));

        $settings->add(new admin_setting_configcheckbox('tool_heartbeat/shouldlogcacheping',
            get_string('settings:shouldlogcacheping:heading', 'tool_heartbeat'),
            get_string('settings:shouldlogcacheping:desc', 'tool_heartbeat'),
            // Since pinging only happens usually once every 24 hrs, we default this on as it is quite lightweight.
            1
        ));

        $settings->add(new admin_setting_configcheckbox('tool_heartbeat/shouldlogcachecheck',
            get_string('settings:shouldlogcachecheck:heading', 'tool_heartbeat'),
            get_string('settings:shouldlogcachecheck:desc', 'tool_heartbeat'),
            // This happens every time the check api cachecheck is called, which is a lot more often than pinging.
            // For e.g. with external monitoring, it could be once or more per minute.
            // So its defaulted to off unless turned on for specific debugging.
            0
        ));
    }
}
