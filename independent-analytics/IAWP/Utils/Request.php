<?php

namespace IAWP\Utils;

use IAWPSCOPED\Illuminate\Support\Str;
use IAWPSCOPED\IPLib\Factory;
/** @internal */
class Request
{
    public static function get_post_array(string $field) : ?array
    {
        if (!\array_key_exists($field, $_POST) || !\is_array($_POST[$field])) {
            return null;
        }
        return \rest_sanitize_array($_POST[$field]);
    }
    public static function get_post_string(string $field) : ?string
    {
        if (!\array_key_exists($field, $_POST)) {
            return null;
        }
        return \sanitize_text_field($_POST[$field]);
    }
    public static function path_relative_to_site_url($url = null)
    {
        if (\is_null($url)) {
            $url = self::url();
        }
        $site_url = \site_url();
        if ($url == $site_url) {
            return '/';
        } elseif (\substr($url, 0, \strlen($site_url)) == $site_url) {
            return \substr($url, \strlen($site_url));
        } else {
            return $url;
        }
    }
    public static function ip()
    {
        if (\defined('IAWP_TEST_IP')) {
            return \IAWP_TEST_IP;
        }
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR', 'HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_INCAP_CLIENT_IP', 'HTTP_CF_CONNECTING_IP'];
        if (\is_string(self::custom_ip_header())) {
            \array_unshift($headers, self::custom_ip_header());
        }
        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                return \explode(',', $_SERVER[$header])[0];
            }
        }
        return null;
    }
    public static function custom_ip_header() : ?string
    {
        // add_filter('iawp_header_with_ip_address', function () {
        //     return 'CUSTOM_HEADER_NAME';
        // });
        $user_defined_header = \apply_filters('iawp_header_with_ip_address', null);
        if (!\is_string($user_defined_header)) {
            return null;
        }
        $user_defined_header = \IAWP\Utils\Security::string($user_defined_header);
        if (Str::of($user_defined_header)->test('/^[a-zA-Z_]+$/')) {
            return $user_defined_header;
        }
        return null;
    }
    public static function user_agent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }
    public static function is_ip_address_blocked() : bool
    {
        $blocked_ips = \IAWPSCOPED\iawp()->get_option('iawp_blocked_ips', []);
        // No address could be blocked
        if (\count($blocked_ips) == 0) {
            return \false;
        }
        $visitor_address = Factory::parseAddressString(self::ip());
        // We cannot block invalid ip addresses
        if ($visitor_address === null) {
            return \false;
        }
        foreach ($blocked_ips as $blocked_ip) {
            $blocked_range = Factory::parseRangeString($blocked_ip);
            // We cannot check an ip address against an invalid range
            if ($blocked_range === null) {
                continue;
            }
            // If the address matches this particular blocked range, then the ip address is indeed blocked
            if ($blocked_range->contains($visitor_address)) {
                return \true;
            }
        }
        return \false;
    }
    private static function scheme()
    {
        if (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https' || !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' || !empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
            return 'https';
        } else {
            return 'http';
        }
    }
    private static function url()
    {
        if (!empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['REQUEST_URI'])) {
            return \esc_url_raw(self::scheme() . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        } else {
            return null;
        }
    }
}
