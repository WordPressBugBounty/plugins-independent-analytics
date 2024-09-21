<?php

namespace IAWP\Utils;

use IAWPSCOPED\Illuminate\Support\Str;
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
        // add_filter('iawp_header_with_ip_address', function () {
        //     return 'CUSTOM_HEADER_NAME';
        // });
        $user_defined_header = \apply_filters('iawp_header_with_ip_address', null);
        if (\is_string($user_defined_header)) {
            $user_defined_header = \IAWP\Utils\Security::string($user_defined_header);
            if (Str::of($user_defined_header)->test('/^[a-zA-Z_]+$/')) {
                \array_unshift($headers, $user_defined_header);
            }
        }
        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                return \explode(',', $_SERVER[$header])[0];
            }
        }
        return null;
    }
    public static function user_agent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }
    public static function is_ip_address_blocked($blocked_ips)
    {
        $visitor_ip = self::ip();
        if (\in_array($visitor_ip, $blocked_ips)) {
            return \true;
        }
        $wildcard_ips = [];
        foreach ($blocked_ips as $blocked_ip) {
            if (\IAWP\Utils\String_Util::str_contains($blocked_ip, '*')) {
                $wildcard_ips[] = $blocked_ip;
            }
        }
        if (\count($wildcard_ips) == 0) {
            return \false;
        }
        $delimeter = \IAWP\Utils\String_Util::str_contains($visitor_ip, '.') ? '.' : ':';
        $visitor_parts = \explode($delimeter, $visitor_ip);
        $goal = \count($visitor_parts);
        foreach ($wildcard_ips as $blocked_ip) {
            $blocked_parts = \explode($delimeter, $blocked_ip);
            $matches = 0;
            for ($i = 0; $i < \count($visitor_parts); $i++) {
                if (!\array_key_exists($i, $blocked_parts)) {
                    $matches++;
                } elseif ($visitor_parts[$i] == $blocked_parts[$i] || $blocked_parts[$i] == '*') {
                    $matches++;
                    continue;
                } else {
                    break;
                }
            }
            if ($matches == $goal) {
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
