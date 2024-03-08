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
 *
 * Heartbeat override
 *
 * @package    tool_heartbeat
 * @copyright  2023 Owen Herbert <owenherbert@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace tool_heartbeat\object;

use core\persistent;

/**
 * Represents a heartbeat override.
 */
class override extends Persistent {

    /**
     * Table name for the persistent.
     */
    const TABLE = 'tool_heartbeat_overrides';

    /**
     * Create an instance of this class with the default expires at.
     *
     * @param int $id If set, this is the id of an existing record, used to load the data.
     * @param \stdClass $record If set will be passed to {@link self::from_record()}.
     */

    public function __construct(int $id = 0, \stdClass $record = null) {
        $this->set_default_expiry();
        parent::__construct($id, $record);
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'ref' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
            ],
            'override' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
            ],
            'userid' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'note' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
            ],
            'url' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_TEXT,
            ],
            'expires_at' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
            ],
            'resolved_at' => [
                'null' => NULL_NOT_ALLOWED,
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }

    /**
     * Sets the expiry time to the default value
     *
     * @return void
     */
    private function set_default_expiry() {
        $expiredate = date('Y-m-d', time() + (int) get_config('tool_heartbeat', 'mutedefault'));
        $this->set('expires_at', strtotime($expiredate));
    }

    /**
     * Resolves and unmutes a check.
     *
     * @param int $time optional param to set a sepcific resolved at time
     * @return void
     */
    public function resolve($time = 0) {
        $time = $time ?: time();
        $this->set('resolved_at', $time);
        $this->save();
    }

    /**
     * Gets an active override based on a ref.
     *
     * @param string $ref ref
     * @return override|null
     */
    public static function get_active_override($ref): ?override {
        // This call will usually be called for every ref, so load all active overrides.
        $overrides = self::get_active_overrides();
        return $overrides[$ref] ?? null;
    }

    /**
     * Returns a list of active overrides with ref as the key.
     *
     * @return array array of overrides, with check ref as the key
     */
    protected static function get_active_overrides(): array {
        static $overrides;

        if (isset($overrides)) {
            return $overrides;
        }

        // Keep resolved status up to date.
        self::mark_expired_as_resolved();

        // Get overrides that and unresolved and expire in the future.
        $overrides = [];
        $conditions = "expires_at >= ? AND resolved_at = 0";
        $results = self::get_records_select($conditions, [time()]);

        // Update key to be the ref, which is unique when unresolved.
        foreach ($results as $result) {
            $ref = $result->get('ref');
            $overrides[$ref] = $result;
        }

        return $overrides;
    }

    /**
     * Returns an instance of a previous override with reset values.
     *
     * @param string $ref
     * @return override|null
     */
    public static function get_restored_override(string $ref): ?override {
        // Make sure there's no active override.
        $active = self::get_active_override($ref);
        if (!empty($active)) {
            return null;
        }

        // Get previous override that has expired or been resolved within the last year.
        $conditions = "ref = ? AND (expires_at < ? OR resolved_at != 0) AND expires_at > ?";
        $params = [$ref, time(), strtotime('-1 year')];
        $previous = self::get_records_select($conditions, $params, 'expires_at DESC, resolved_at DESC', '*', 0, 1);
        if (empty($previous)) {
            return null;
        }

        // Copy record and clear previous time and id data so a new record is created.
        $override = reset($previous);
        $override->set('id', 0);
        $override->set('resolved_at', 0);
        $override->set_default_expiry();
        return $override;
    }

    /**
     * Marks all expired overrides as resolved
     *
     * @return void
     */
    protected static function mark_expired_as_resolved() {
        $conditions = "expires_at < ? AND resolved_at = 0";
        $overrides = self::get_records_select($conditions, [time()]);
        foreach ($overrides as $override) {
            $override->resolve();
        }
    }

    /**
     * Returns the human readable time until mute ends
     *
     * @return string
     */
    public function get_time_until_mute_ends(): string {
        $enddate = userdate($this->get('expires_at'), get_string('strftimedate', 'langconfig'));
        $remainingtime = format_time($this->get('expires_at') - time());
        return "$enddate ($remainingtime)";
    }
}
