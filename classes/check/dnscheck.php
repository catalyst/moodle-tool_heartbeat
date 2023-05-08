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
 * DNS security check.
 *
 * @package    tool_heartbeat
 * @copyright  2023 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace tool_heartbeat\check;
use core\check\check;
use core\check\result;

/**
 * DNS check class.
 *
 * @copyright  2023
 * @author     Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dnscheck extends check {

    /**
     * Get Result.
     *
     * @return result
     */
    public function get_result() : result {
        global $DB, $CFG;

        $url = new \moodle_url($CFG->wwwroot);
        $domain = $url->get_host();

        $details = '';
        $status = result::INFO;
        $summary = '';

        // Is this site using a CNAME and typically on a hosting providers subdomain?
        $result = @dns_get_record($domain . '.', DNS_CNAME);
        if (!empty($result)) {
            $cname = $result[0]['target'];
            $base = $this->get_base_domain($cname);

            if ($base != $cname) {
                $summary = "CNAME to $base subdomain";
            } else {
                $summary = "CNAME to $base";
            }
            $details .= "<code>$domain</code> is a CNAME to <code>$cname</code>";
            return new result($status, $summary, $details);
        }

        // Is this site using A records?
        $result = dns_get_record($domain, DNS_A);  // This will silently follow cname's to IPs.
        if (!empty($result)) {
            $ips = array_map(function($value) {
                return $value['ip'];
            }, $result);
            sort($ips);

            $ip = $ips[0];
            $ips = join(', ', $ips);
            $details .= "<p>$domain is an A record to $ips</p>";

            // If the IP is public lets try to dig up some more info on who own's the IP space.
            if (ip_is_public($ip)) {

                $curl = new \curl();
                $whoishtml = $curl->get('https://who.is/whois-ip/ip-address/104.22.38.234');

                if (preg_match('/OrgName\:(.*)OrgId/sim', $whoishtml, $match)) {

                    $nethandle = clean_param(trim($match[1]), PARAM_TEXT);

                    $summary = "A record to '$nethandle' IP's: $ips";
                } else {
                    $summary = "A record to $ips";

                }
            } else {
                $summary = 'A record to private IP space';
            }

        } else {
            $summary = 'Unknown DNS setup';
            $details = "$domain is not a CNAME or A record?";
        }

        return new result($status, $summary, $details);
    }

    /**
     * Find the top level domain
     *
     * @return domain
     */
    public function get_base_domain($domain) {
        $parts = explode('.', $domain);
        $size = count($parts);
        for ($c = $size - 1; $c >= 0; $c--) {
            $end = join('.', array_slice($parts, $c, $size));
            $result = @dns_get_record($end . '.', DNS_A);
            if (!empty($result)) {
                return $end;
            }
        }
        return $domain;
    }

}
