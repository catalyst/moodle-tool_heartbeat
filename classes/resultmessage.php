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

namespace tool_heartbeat;

/**
 * A data-only class for holding a message about a result from a check API class.
 *
 * @package   tool_heartbeat
 * @author    Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright 2023, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class resultmessage {
    /** @var int OK level **/
    public const LEVEL_OK = 0;

    /** @var int WARN level **/
    public const LEVEL_WARN = 1;

    /** @var int CRITICAL level **/
    public const LEVEL_CRITICAL = 2;

    /** @var int UNKNOWN level **/
    public const LEVEL_UNKNOWN = 3;

    /** @var int $level The level of this message **/
    public $level = self::LEVEL_UNKNOWN;

    /** @var string $title Title of the message **/
    public $title = '';

    /** @var string $message Details of this message **/
    public $message = '';
}

