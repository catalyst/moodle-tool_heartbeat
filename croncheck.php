<?php

require('../../../config.php');

$max = optional_param('max', 6, PARAM_NUMBER);
$cronmax = $max * 60 * 60;

$lastcron = $DB->get_field_sql('SELECT MAX(lastcron) FROM {modules}');
$currenttime = time();
$difference = $currenttime - $lastcron;

if( $difference > $cronmax ) {
    printf ("MOODLE CRON ERROR RAN %d:%02d HOURS AGO (> $max)\n", floor($difference/60/60), floor($difference/60) % 60);
    exit(2);
} else {
    print "MOODLE CRON OK\n";
    exit(0);
}

