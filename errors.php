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
 * Tests all the different types of error classes
 *
 * @package    tool_heartbeat
 * @copyright  2020 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreStart
require_once('../../../config.php');
// @codingStandardsIgnoreEnd

require_once(__DIR__.'/iplock.php');

$syscontext = context_system::instance();
$PAGE->set_url('/admin/tool/heartbeat/errors.php');
$PAGE->set_context($syscontext);
echo $OUTPUT->header();
echo $OUTPUT->heading('Tests for all errors in the whole stack');

?>
<h3>Moodle high level errors</h3>
<p>These should all use the moodle theme</p>
<li>A 404 for a themed moodle script which returns 404 <a href='errors/moodleerror.php?code=404'>moodleerror.php?code=404</a></li>
<li>A 405 for a themed moodle script which returns 405 <a href='errors/moodleerror.php?code=405'>moodleerror.php?code=405</a></li>
<li>A 404 for a themed moodle script which throws moodle_exception <a href='errors/moodleexception.php'>moodleexception.php</a></li>
<li>A 404 for a themed moodle script which throws raw exception <a href='errors/exception.php'>exception.php</a></li>
<li>A 404 for a themed moodle 404 page (little known and rarely used) <a href='/error/'>/error/</a></li>

<h3>Moodle low level errors</h3>
<p>These are errors early in the bootstrap before the theme can load</p>
<li>A 503 for an unthemed moodle script which fails early in the bootstrap
    <a href='errors/bootstrap-early.php'>bootstrap-early.php</a></li>
<li>A 503 for a vanilla themed moodle script which fails late in the bootstrap
    <a href='errors/bootstrap-late.php'>bootstrap-late.php</a></li>

<h3>PHP level errors and setup</h3>
<p>These are errors are awkward to theme because they can appear in the middle of a themed page</p>
<li>A 200 unthemed moodle script which 'Fatal errors' times out after 1 second
    <a href='errors/moodletimeout.php'>moodletimeout.php</a></li>
<li>A 200 unthemed moodle script which 'Fatal errors' times out after exceding memory
    <a href='errors/moodlememory.php'>moodlememory.php</a></li>
<li>A 500 page with 0 byte response for a broken script which doesn't even compile
    <a href='errors/compile.php'>compile.php</a></li>

<h3>Web server level errors</h3>
<p>These errors are potentially themable either using moodle or at the web server level.</p>
<li>A 301 redirect for a directory with no slash <a href='/my'>/my</a></li>
<li>A 404 for a file which doesn't exist <a href='doesnotexist.html'>doesnotexist.html</a></li>
<li>A 403 for a directory listing <a href='/lib/classes/'>/lib/classes/</a></li>

<h3>Reverse proxy / varnish / cdn / load balancer tests</h3>
<p>Thes are difficult to simulate, YMMV. These should be themed independantly with static pages.</p>
<li>A 504 Gateway timeout page, moodle just sleeps for 300 seconds (tunable)
    <a href='errors/moodlesleep.php?time=300'>errors/moodlesleep.php?time=300</a></li>
<li>A 502 Bad Gateway, proxy cannot connect at all (how to test?)</li>

<?php

echo $OUTPUT->footer();

