<?php

/**
 * Class MemcachedUtil.
 * Contains Memcached Utility function which has been made inaccessible in moodle core.
 */
abstract class MemcachedUtil {
    /**
     * Convert a connection string to an array of servers
     *
     * EG: Converts: "abc:123, xyz:789" to
     *
     *  array(
     *      array('abc', '123'),
     *      array('xyz', '789'),
     *  )
     *
     * @copyright  2013 Moodlerooms Inc. (http://www.moodlerooms.com)
     * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     * @author     Mark Nielsen
     *
     * @param string $str save_path value containing memcached connection string
     * @return array
     */
    public static function connection_string_to_memcache_servers($str) {
        $servers = array();
        $parts   = explode(',', $str);
        foreach ($parts as $part) {
            $part = trim($part);
            $pos  = strrpos($part, ':');
            if ($pos !== false) {
                $host = substr($part, 0, $pos);
                $port = substr($part, ($pos + 1));
            } else {
                $host = $part;
                $port = 11211;
            }
            $servers[] = array($host, $port);
        }
        return $servers;
    }
}
