# Testing "Performance log stream"

## Workstation/server

Setting `tool_heartbeat | logstream` to both a file path, eg `/tmp/perf.log' or a syslog url, eg `syslog://local0/TEST` or just `syslog://local0/` should just work.

## Docker container

I test with standard `php:[Maj].[Min].-cli` base, running the ap as `php -S 0.0.0.0:80`. That should not matter, but just for the reference.

Whatever your way is, Setting `tool_heartbeat | logstream` to a file path should just work. Of course, if the destination is not mounted you'll need to get into the container and check the file.

### Syslog

To get syslog to work, I did following:

* Installer `rsyslog` in the image: `Dockerfile: apt-get install rsyslog`
* mounted host's `/dev/log`: `docker run ... -v /dev/log:/dev/log ...`

That makes container's syslog going straight to your workstation's syslog, so you can check your `/var/log/syslog`.