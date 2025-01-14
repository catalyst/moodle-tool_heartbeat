[![ci](https://github.com/catalyst/moodle-tool_heartbeat/actions/workflows/ci.yml/badge.svg?branch=MOODLE_39_STABLE)](https://github.com/catalyst/moodle-tool_heartbeat/actions/workflows/ci.yml?branch=MOODLE_39_STABLE)

# A heartbeat test page for Moodle

- [A heartbeat test page for Moodle](#a-heartbeat-test-page-for-moodle)
- [Branches](#branches)
- [What is this?](#what-is-this)
  - [Front end health](#front-end-health)
  - [Application health](#application-health)
  - [Failed login detection](#failed-login-detection)
- [Installation](#installation)
- [Configuration](#configuration)
- [Testing](#testing)

# What is this?

This plugin exposes various endpoints that can be wired to load balancers and monitoring systems to help expose when things go wrong.

NOTE: In an ideal world this plugin should be redundant and most of it's functionality built into core as a new API, enabling each plugin to delare it's own extra health checks. See:

https://tracker.moodle.org/browse/MDL-47271

# Branches

| Branch             | Moodle version    | PHP Version |
| ------------------ | ----------------- | ----------- |
| master             | Moodle 2.7 - 4.1  | Php 5.4.4+  |
| MOODLE_39_STABLE   | Moodle 3.9 +      | Php 7.2+    |

The master branch retains very deep support for old Totara's and Moodle's back to Moodle 2.7.

For any site using Moodle 3.9 or later, it is recommended to use the MOODLE_39_STABLE branch.

The MOODLE_39_STABLE branch uses the [Check API](https://moodledev.io/docs/apis/subsystems/check) exclusively, which simplifies the code massively.

## Versioning

Versioning follows the [Moodle versioning guidelines](https://moodledev.io/docs/apis/commonfiles/version.php#version)

Whenever a version change is required:
- The `master` branch should always be `20231024xx` where `xx` increases by 1 each time.
- The `MOODLE_39_STABLE` branch should always be updated to the current date.

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

Named `croncheck.php` for compatibility with older versions of this plugin, this page executes all `status` check API checks, and shows any that return non-ok results.

It is a nagios compliant checker to see if cron or any individual tasks are failing, with configurable thresholds

This script can be either run from the web:

http://moodle.example.com/admin/tool/heartbeat/croncheck.php

Or can be run as a CLI in which case it will return in the format expected by Nagios:

```
sudo -u www-data php /var/www/moodle/admin/tool/heartbeat/croncheck.php
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


# Installation

Best to always use the latest version from this git repo:

https://github.com/catalyst/moodle-tool_heartbeat


Or via the Moodle plugin directory (which may be out of date)

https://moodle.org/plugins/view/tool_heartbeat


# Configuration

http://moodle.local/admin/settings.php?section=tool_heartbeat

* Set a fake warning state of 'error' or 'warn'
* By default in a new install this is set to 'error'. This is done intertionally so that you know your monitoring is wired up correctly end to end. You should see you monitoring raise an alert which tells you that it is a test and links to the admin setting to turn it into normal monitoring mode.
* Optionaly lock down the endpoints by IP

## Check maximum alerting level configuration
This plugin allows configuring maximum permitted alerting level of tasks in the config.php

This is supplied as list of regex tests and their associated configuration array.

The configuration is stored in the site config.php under the config setting $CFG->tool_heartbeat_check_defaults

This should be an array with a form like the following
```php
$CFG->tool_heartbeat_check_defaults = [
    '.+_task_.+' => [
        'maxwarninglevel' => 'info',
    ],
    'core_task_tag_cron_task' => [
        'maxwarninglevel' => 'critical',
    ],
    'tool_task_.*' => [
        'maxwarninglevel' => 'critical',
    ],
];
```

Each item is tested via `preg_match` against the globally unique check reference string for the check, if it matches, the max warning level configuration is applied, this applies each item in the array from first to last, so if a check matches more than once, the value that is latest in the array is used, this allows setting more broad defaults and then increasing specificity for specific checks to allow them to override the broader defaults.



# Testing

When you first setup this plugin and have wired it end to end with Nagios / Icinga or another monitoring tool, you want the peace of mind to know that it is all correctly working. There is a setting which allows you to send a fake warning so you can confirm your pager will go off. This setting is set to 'error' by default by design

http://moodle.local/admin/settings.php?section=tool_heartbeat
