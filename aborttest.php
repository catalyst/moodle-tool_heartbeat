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
 * Performs a request abort test
 *
 * @package    tool_heartbeat
 * @copyright  2019 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_OUTPUT_BUFFERING', true);

require(__DIR__ . '/../../../config.php');
require_login();

$stage       = optional_param('stage', 1,   PARAM_NUMBER);
$ignoreabort = optional_param('ignoreabort', 0, PARAM_NUMBER);

if ($ignoreabort) {
    ignore_user_abort(true);
    // Worst case it should die in 5 seconds.
    set_time_limit(5);
}

$syscontext = context_system::instance();
$url = new moodle_url('/admin/tool/heartbeat/aborttest.php');
$PAGE->set_url($url);
$PAGE->set_context($syscontext);
$PAGE->set_pagelayout('standard');
$PAGE->set_cacheable(false);
$url->params(['stage' => 2]);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('testabort', 'tool_heartbeat'));

echo get_string('testaborthelp', 'tool_heartbeat');
echo "<h3>Stage: $stage</h3>";

if ($stage == 2) {
    $progress = $SESSION->abortprogress;

    if ($progress > 0 && $progress < 100) {
        echo $OUTPUT->notification("Yay! the request was correctly aborted at {$progress}%", \core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $OUTPUT->notification("Doh! the request was not aborted: {$progress}%", \core\output\notification::NOTIFY_ERROR);
    }

    echo "<p><a class='btn btn-primary' href='aborttest.php'>Start again</a></p>";
    echo "<p><a class='btn btn-danger' href='aborttest.php?ignoreabort=1'>Start again with ignore_user_abort</a></p>";

    echo $OUTPUT->footer();
}

if ($stage == 1) {
    echo <<<EOF
<p>This should show a moving progress bar, but after 1 seconds the page should reload and it should NOT get to 100%.</p>

<script>
setTimeout(function(){
    window.stop();
    location.href = '{$url->out()}';
},1000);
</script>
EOF;

    $progressbar = new progress_bar();
    $progressbar->create();

    echo $OUTPUT->footer();

    $SESSION->abortprogress = 0;

    $totalseconds = 10;
    $progressbar->update_full(0, '0%');
    for ($c = 1; $c <= 100; $c += .1) {
        usleep(10000);
        $progressbar->update_full($c, sprintf('%.1f%%', $c));
        $SESSION->abortprogress = $c;
        if (connection_status() != CONNECTION_NORMAL) {
            // @codingStandardsIgnoreStart
            error_log("Aborting stage 1 at $c %");
            // @codingStandardsIgnoreEnd
        }
    }
    $SESSION->abortprogress = 100;
}

