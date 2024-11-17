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

use core\check\check;
use core\check\result;
use Throwable;

/**
 * Check API checker class
 *
 * Processes check API results and returns them in a nice format for nagios output.
 *
 * @package   tool_heartbeat
 * @author    Matthew Hilton <matthewhilton@catalyst-au.net>
 * @copyright 2023, Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checker {
    /** @var array Nagios level prefixes **/
    public const NAGIOS_PREFIXES = [
        0 => "OK",
        1 => "WARNING",
        2 => "CRITICAL",
        3 => "UNKNOWN",
    ];

    /** @var array Map check result to nagios level **/
    public const RESULT_MAPPING = [
        result::OK => resultmessage::LEVEL_OK,
        result::INFO => resultmessage::LEVEL_OK,
        result::NA => resultmessage::LEVEL_OK,
        result::WARNING => resultmessage::LEVEL_WARN,
        result::CRITICAL => resultmessage::LEVEL_CRITICAL,
        result::ERROR => resultmessage::LEVEL_CRITICAL,
        result::UNKNOWN => resultmessage::LEVEL_UNKNOWN,
    ];

    /** @var array Map check result to nagios level **/
    public const RESULT_ORDER = [
        result::NA          => 0,
        result::INFO        => 1,
        result::OK          => 2,
        result::WARNING     => 3,
        result::UNKNOWN     => 4,
        result::ERROR       => 5,
        result::CRITICAL    => 6,
    ];

    /**
     * Returns an array of check API messages.
     * If exceptions are thrown, they are caught and returned as result messages as well.
     * Note - OK results are not returned.
     *
     * @return array array of resultmessage objects
     */
    public static function get_check_messages(): array {
        // First try to get the checks, if this fails return a critical message (code is very broken).
        $checks = [];

        try {
            $checks = \core\check\manager::get_checks('status');
        } catch (Throwable $e) {
            return [self::exception_to_message("Error getting checks: ", $e)];
        }

        // Remove any supressed checks from the list.
        $checks = self::remove_supressed_checks($checks);

        // Execute each check and store their messages.
        $messages = [];

        foreach ($checks as $check) {
            try {
                $messages[] = self::process_check_and_get_result($check);
            } catch (Throwable $e) {
                $messages[] = self::exception_to_message("Error processing check " . $check->get_ref() . ": ", $e);
            }
        }

        // Add any output buffer message.
        $messages[] = self::get_ob_message();

        // Filter out any OK messages, we don't care about these.
        $messages = array_filter($messages, function($m) {
            return $m->level != resultmessage::LEVEL_OK;
        });

        return $messages;
    }

    /**
     * Closes the output buffering, and if anything was outputted, a warning resultmessage is returned
     * @return resultmessage
     */
    private static function get_ob_message(): resultmessage {
        $contents = ob_get_clean() ?: '';

        // Default to OK.
        $res = new resultmessage();
        $res->level = resultmessage::LEVEL_OK;
        $res->title = 'Output buffering: No output buffered';
        $res->message = 'No output buffered';

        if (!empty($contents)) {
            $res->level = resultmessage::LEVEL_WARN;
            $res->title = "Output buffering: Unexpected output";
            $res->message = $contents;
        }

        // Process these using the HTML cleaning function.
        list($title, $message) = self::process_title_and_message($res->title, $res->message, "");
        $res->title = $title;
        $res->message = $message;

        return $res;
    }

    /**
     * Turns the given exception into a warning resultmessage.
     * @param string $prefix
     * @param Throwable $e
     * @return resultmessage
     */
    private static function exception_to_message(string $prefix, Throwable $e): resultmessage {
        $res = new resultmessage();
        $res->level = resultmessage::LEVEL_WARN;
        $res->title = $prefix . $e->getMessage();
        $res->message = (string) $e;
        return $res;
    }

    /**
     * Processes the check and maps its result and status to a resultmessage.
     * @param check $check
     * @return resultmessage
     */
    private static function process_check_and_get_result(check $check): resultmessage {
        $res = new resultmessage();

        $checkresult = self::get_overridden_result($check);

        // Map check result to nagios level.
        $map = self::RESULT_MAPPING;

        // Get the level, or default to unknown.
        $status = $checkresult->get_status();
        $res->level = isset($map[$status]) ? $map[$status] : resultmessage::LEVEL_UNKNOWN;

        list($title, $message) = self::process_title_and_message($check->get_name(), $checkresult->get_summary(),
            $checkresult->get_details());
        $res->title = $title;
        $res->message = $message;

        return $res;
    }

    /**
     * Parses, cleans and sets up the correct output.
     * @param string $title
     * @param string $summary
     * @param string $details
     * @return array array of [$title, $message]
     */
    private static function process_title_and_message(string $title, string $summary, string $details): array {
        // Strip tags from summary and details.
        $summary = self::clean_text($summary);
        $details = self::clean_text($details);

        // Get all the lines of the message.
        $messagelines = explode("\n", $summary);
        $messagelines = array_merge($messagelines, explode("\n", $details));

        // Clean each one.
        $messagelines = array_map(function($line) {
            return self::clean_text($line);
        }, $messagelines);

        // Remove empty lines.
        $messagelines = array_filter($messagelines);

        // Use the first line in the title.
        $title .= ": " . array_shift($messagelines);

        // Use the rest in the message.
        $message = implode("\n", $messagelines);

        return [$title, $message];
    }

    /**
     * Cleans the text ready for output.
     * @param string $text
     * @return string
     */
    private static function clean_text(string $text): string {
        // Convert any line breaks to newlines.
        $text = str_replace("<br />", "\n", $text);
        $text = str_replace("<br>", "\n", $text);

        // Strip tags.
        $text = strip_tags($text);

        // Clean any pipe characters from the $msg. This is because pipe characters
        // separate Nagios performance data from log data.
        // They are replaced with a unicode lookalike.
        // https://www.compart.com/en/unicode/U+FF5C .
        $text = str_replace("|", "｜", $text);

        // Strip extra newlines.
        $text = trim($text);

        return $text;
    }

    /**
     * From an array of resultmessage, determines the highest nagios level.
     * Note, it considers UNKNOWN to be less than CRITICAL or WARNING.
     *
     * @param array $messages array of resultmessage objects
     * @return int the calculated nagios level
     */
    public static function determine_nagios_level(array $messages): int {
        // Find the highest level.
        $levels = array_column($messages, "level");

        // Add a default "OK" in case no messages were returned.
        $levels[] = resultmessage::LEVEL_OK;

        $hasunknown = in_array(resultmessage::LEVEL_UNKNOWN, $levels);

        // Remove unknowns.
        $levels = array_filter($levels, function($l) {
            return $l != resultmessage::LEVEL_UNKNOWN;
        });

        $highestwithoutunknown = max($levels);

        // If highest was OK but it had an UNKNOWN, return UNKNOWN.
        // This stops UNKNOWN from masking WARNING or CRITICAL.
        if ($highestwithoutunknown == resultmessage::LEVEL_OK && $hasunknown) {
            return resultmessage::LEVEL_UNKNOWN;
        }

        // Else return the highest.
        return $highestwithoutunknown;
    }

    /**
     * Creates a summary from the given messages.
     * If there are no messages or only OK, OK is returned.
     * If there is a single message, its details are returned.
     * If there are multiple messages, the levels are aggregated and turned into a summary.
     *
     * @param array $messages array of resultmessage objects
     * @return string
     */
    public static function create_summary(array $messages): string {
        // Filter out any OK messages.
        // Usually they are filtered out already, but in case they aren't.
        $messages = array_filter($messages, function($m) {
            return $m->level != resultmessage::LEVEL_OK;
        });

        // If no messages, return OK.
        if (count($messages) == 0) {
            return "OK";
        }

        // If only one message, use it as the top level.
        if (count($messages) == 1) {
            return self::clean_text(current($messages)->title);
        }

        // Otherwise count how many of each level.
        $counts = array_count_values(array_column($messages, 'level'));

        $countswithprefixes = [];
        foreach ($counts as $level => $occurrences) {
                $prefix = self::NAGIOS_PREFIXES[$level];
             $countswithprefixes[] = "{$occurrences} {$prefix}";
        }

        return "Multiple problems detected: " . implode(", ", $countswithprefixes);
    }

    /**
     * Stores any checks that are suppressed/ignored by this class.
     * @return array array of class name strings of checks to ignore
     */
    private static function supressed_checks(): array {
        return [
            // This task is supressed and replaced by a more detailed/useful version in this plugin.
            // See failingtaskcheck.php.
            \tool_task\check\maxfaildelay::class,
        ];
    }

    /**
     * Removes supressed checks from an array
     * @param array $checks
     * @return array of checks without supressed checks
     */
    public static function remove_supressed_checks(array $checks): array {
        // Remove any supressed checks from the list.
        return array_filter($checks, function($check) {
            return !in_array(get_class($check), self::supressed_checks());
        });
    }

    /**
     * Apply any configured changes to the result of the check based on it's ref string
     *
     * Right now the only configurable options are to reduce the maximum alert level of a check.
     * @param string $ref The check ref value
     * @param core\check\result $result The default result of the check
     * @return core\check\result The result of the check after any configuration settings are applied
     */
    public static function apply_configuration_settings($ref, result $result): result {
        global $CFG;
        // No configuration exists, short circuit.
        if (!isset($CFG->tool_heartbeat_check_defaults)
            || !is_array($CFG->tool_heartbeat_check_defaults)
        ) {
            return $result;
        }
        $status = $result->get_status();
        $max = false;
        // The configuration is a list of potential regex strings => array of
        // config, we check each regex string against the check ref.
        // Note: We do not guard against multiple matches, the last in the array
        // always applies.
        $tests = array_keys($CFG->tool_heartbeat_check_defaults);
        foreach ($tests as $test) {
            $regex = '/'.$test.'/';
            if (preg_match($regex, $ref)) {
                // This key matched, get the maximum fail delay.
                $max = $CFG->tool_heartbeat_check_defaults[$test]['maxwarninglevel'];
            }
        }
        // None of the configuration options matched, just return the passed in
        // result.
        if ($max === false) {
            return $result;
        }
        // Get a map of result string to integers representing their "order level".
        $map = self::RESULT_ORDER;
        // Get the order value of each status.
        $maxint = $map[$max];
        $realint = $map[$status];
        // Determine the lowest ordered status of the two.
        $finalint = min($maxint, $realint);
        // Flip the array to be integer => string constant and return the allowed
        // final status.
        $status = array_flip($map)[$finalint];
        return new result($status, $result->get_summary(), $result->get_details());
    }
    /**
     * Gets a check result while applying specified overrides.
     * @param check $check
     * @return result with overrides
     */
    public static function get_overridden_result(check $check): result {
        $ref = $check->get_ref();
        $result = $check->get_result();

        // Apply any configured global configuration options to the result.
        $result = self::apply_configuration_settings($ref, $result);

        $override = \tool_heartbeat\object\override::get_active_override($ref);
        if (!isset($override)) {
            return $result;
        }

        // Mutes should only be able to lower the level.
        $map = self::RESULT_MAPPING;
        $status = $override->get('override');
        if ($map[$status] > $map[$result->get_status()]) {
            // Don't automatically resolve the override as some checks may fail sporadically.
            return $result;
        }

        return new result($status, $result->get_summary(), $result->get_details());
    }
}
