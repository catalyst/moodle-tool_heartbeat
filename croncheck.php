<?php

require('../../../config.php');

$max = optional_param('max', 6, PARAM_NUMBER);
$cronmax = $max * 60 * 60;

$lastcron = $DB->get_field_sql('SELECT MAX(lastruntime) FROM {task_scheduled}');
$currenttime = time();
$difference = $currenttime - $lastcron;

if( $difference > $cronmax ) {
    printf ("MOODLE CRON ERROR LAST RAN %d days %02d:%02d hours AGO (> $max hours)\n",
        floor($difference/60/60/24),
        floor($difference/60/60) % 24,
        floor($difference/60) % 60
    );
    exit(2);
}

$delay = '';
$tasks = core\task\manager::get_all_scheduled_tasks();
foreach ($tasks as $task) {
    if ($task->get_disabled()) {
        continue;
    }
    $faildelay = $task->get_fail_delay();
    if ($faildelay == 0){
        continue;
    }
    $delay .= "TASK: " . $task->get_name() . ' (' .get_class($task) . ") Delay: $faildelay\n";
}
if ($delay){
    print "MOODLE CRON OK BUT WITH TASK FAIL DELAYS:\n$delay";
    exit(2);
} else {
    print "MOODLE CRON OK\n";
    exit(0);
}

