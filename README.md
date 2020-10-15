<a href="https://travis-ci.org/catalyst/moodle-tool_heartbeat">
<img src="https://travis-ci.org/catalyst/moodle-tool_heartbeat.svg?branch=master">
</a>

# A heartbeat test page for Moodle

* [What is this?](#what-is-this)
* [Branches](#branches)
* [Installation](#installation)
* [Configuration](#configuration)
* [Testing](#testing)

# What is this?

This plugin exposes various endpoint that can be wired to load balancers and monitoring systems to help expose when this go wrong.

NOTE: In an ideal world this plugin should be redundant and most of it's functionality built into core as a new API, enabling each plugin to delare it's own extra health checks. See:

https://tracker.moodle.org/browse/MDL-47271


## Front end health

This is the ```index.php``` check, and is designed to only assert that the front end is healthy and was intended for use as a load balancer test.

eg it might chech the *connection* to the filesystem, but not stress too much about the health of the filesystem itself. The reason for this is that front end health checks that fail for the wrong reasons pull nodes from the load balancer for no reason.

http://moodle.example.com/admin/tool/heartbeat/

It will return a page with either a 200 or 503 response code and if it fails a string for why.

By default it only performs a light check, in particular it does not check the moodle database. To do a full check add this query param:

http://moodle.example.com/admin/tool/heartbeat/?fullcheck

This check can also be run as a CLI:

```
php index.php fullcheck
```

**Example return values for heartbeat**

Example for when the server is healthy.
```
(HTTP 200)
Server is ALIVE
sitedata OK
```

Example for when the server is in command line maintenace mode.
```
(HTTP 200)
Server is in MAINTENANCE
sitedata OK
```

Example for when the server is not healthy.
```
(HTTP 503)
Server is DOWN
Failed: database error
```



## Application health

This is the croncheck.php - it is mostly, and was originally only around the cron queues, but has grown to cover other aspects.

It is a nagios compliant checker to see if cron or any individual tasks are failing, with configurable thresholds

This script can be either run from the web:

http://moodle.example.com/admin/tool/heartbeat/croncheck.php

Or can be run as a CLI in which case it will return in the format expected by Nagios:

```
sudo -u www-data php /var/www/moodle/admin/tool/heartbeat/croncheck.php
```

The various thresholds can be configured with query params or cli args see this for details:

```
php croncheck.php -h
```


## Failed login detection

The script loginchecker is a nagios compliant checker to monitor the number of failed login attempts on a Moodle site as a security intrusion detection mechanic, with configurable thresholds.

This script can be either run from the web:

http://moodle.example.com/admin/tool/heartbeat/loginchecker.php

Or can be run as a CLI in which case it will return in the format expected by Nagios:

```
sudo -u www-data php /var/www/moodle/admin/tool/heartbeat/loginchecker.php
```

The various thresholds can be configured with query params or cli args see this for details:

```
php loginchecker.php -h
```

# Branches

The master branch is always stable and should retain very deep support for old Totara's and Moodle's back to Moodle 2.7

For this reason we will continue to support php5 for some time.


# Installation

Best to always use the latest version from this git repo:

https://github.com/catalyst/moodle-tool_heartbeat


Or via the Moodle plugin directory (which may be out of date)

https://moodle.org/plugins/view/tool_heartbeat


# Configuration

http://moodle.local/admin/settings.php?section=tool_heartbeat

* Set a fake warning state of 'error' or 'warn'
* Optionaly lock down the endpoints by IP


# Testing

When you have first setup this plugin and wired it end to end with Nagios / Icinga or another monitoring tool, you want the peace of mind to know that it is all correctly working. There is a setting which allows you to send a fake warning so you can confirm your pager will go off. This setting is set to 'error' by default by design

http://moodle.local/admin/settings.php?section=tool_heartbeat
