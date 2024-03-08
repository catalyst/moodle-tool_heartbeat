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
 * Privacy Subsystem implementation for tool_heartbeat.
 *
 * @package    tool_heartbeat
 * @copyright  2018 Olivier SECRET <olivier.secret@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_heartbeat\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use tool_heartbeat\object\override;

/**
 * Privacy provider.
 *
 * @copyright  2018 Olivier SECRET <olivier.secret@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            override::TABLE,
            [
                'note' => 'privacy:metadata:tool_heartbeat_overrides:note',
                'url' => 'privacy:metadata:tool_heartbeat_overrides:url',
                'userid' => 'privacy:metadata:tool_heartbeat_overrides:userid',
                'usermodified' => 'privacy:metadata:tool_heartbeat_overrides:usermodified',
                'timecreated' => 'privacy:metadata:tool_heartbeat_overrides:timecreated',
                'timemodified' => 'privacy:metadata:tool_heartbeat_overrides:timemodified',
            ],
            'privacy:metadata:tool_heartbeat_overrides'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                $list = [];
                // Overrides with a matching userid.
                $rows = $DB->get_records(override::TABLE, ['userid' => $userid]);
                foreach ($rows as $row) {
                    $list[] = [
                        'userid' => $userid,
                        'note' => $row->note,
                        'url' => $row->url,
                        'timecreated' => $row->timecreated,
                    ];
                }
                // Overrides with a matching usermodified.
                $rows = $DB->get_records(override::TABLE, ['usermodified' => $userid]);
                foreach ($rows as $row) {
                    $list[] = [
                        'usermodified' => $userid,
                        'note' => $row->note,
                        'url' => $row->url,
                        'timemodified' => $row->timemodified,
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:tool_heartbeat_overrides', 'tool_heartbeat')],
                    (object) $list
                );
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records(override::TABLE, []);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                $DB->delete_records(override::TABLE, ['userid' => $userid]);
                $DB->delete_records(override::TABLE, ['usermodified' => $userid]);
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $sql = "SELECT * FROM {tool_heartbeat_overrides}";
            $userlist->add_from_sql('userid', $sql, []);
            $sql = "SELECT * FROM {tool_heartbeat_overrides}";
            $userlist->add_from_sql('usermodified', $sql, []);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $users = $userlist->get_users();
            foreach ($users as $user) {
                $DB->delete_records(override::TABLE, ['userid' => $user->id]);
                $DB->delete_records(override::TABLE, ['usermodified' => $user->id]);
            }
        }
    }
}
