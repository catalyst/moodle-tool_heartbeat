<?php

require('../../../config.php');

$max = optional_param('max', 6, PARAM_NUMBER);
$cronmax = $max * 60 * 60;

$lastcron = $DB->get_field_sql('SELECT MAX(lastcron) FROM {modules}');
$currenttime = time();
$difference = $currenttime - $lastcron;

if( $difference > $cronmax ) {
    printf ("MOODLE CRON ERROR LAST RAN %d days %02d:%02d hours AGO (> $max hours)\n",
        floor($difference/60/60/24),
        floor($difference/60/60) % 24,
        floor($difference/60) % 60
    );
    exit(2);
} else {
    print "MOODLE CRON OK\n";
    exit(0);
}

