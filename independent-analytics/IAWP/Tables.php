<?php

namespace IAWP;

/** @internal */
class Tables
{
    public static function views() : string
    {
        return self::prefix('views');
    }
    public static function sessions() : string
    {
        return self::prefix('sessions');
    }
    public static function clicks() : string
    {
        return self::prefix('clicks');
    }
    public static function click_targets() : string
    {
        return self::prefix('click_targets');
    }
    public static function clicked_links() : string
    {
        return self::prefix('clicked_links');
    }
    public static function link_rules() : string
    {
        return self::prefix('link_rules');
    }
    public static function resources() : string
    {
        return self::prefix('resources');
    }
    public static function reports() : string
    {
        return self::prefix('reports');
    }
    public static function orders() : string
    {
        return self::prefix('orders');
    }
    public static function forms() : string
    {
        return self::prefix('forms');
    }
    public static function form_submissions() : string
    {
        return self::prefix('form_submissions');
    }
    private static function prefix(string $name)
    {
        global $wpdb;
        return $wpdb->prefix . 'independent_analytics_' . $name;
    }
}
