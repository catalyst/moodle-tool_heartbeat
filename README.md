# A heartbeat test page for Moodle

Very simple, just performs a quick check of all critical service
dependancies (filesystem, DB, caches, and sessions) and return OK

Use from a load balancer to tell whether a node is OK

A second script croncheck is a nagios compliant checker to see if cron
or any individual tasks are failing, with configurable thresholds

