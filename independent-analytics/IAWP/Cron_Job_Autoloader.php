<?php

namespace IAWP;

use IAWP\Ecommerce\SureCart_Cron_Job;
/** @internal */
abstract class Cron_Job_Autoloader
{
    private static $classes = [SureCart_Cron_Job::class];
    private static $has_registered_custom_intervals = \false;
    public static function schedule() : void
    {
        self::register_custom_intervals();
        foreach (self::$classes as $class) {
            (new $class())->schedule();
        }
    }
    public static function unschedule() : void
    {
        self::register_custom_intervals();
        foreach (self::$classes as $class) {
            (new $class())->unschedule();
        }
    }
    public static function register_handler() : void
    {
        self::register_custom_intervals();
        foreach (self::$classes as $class) {
            (new $class())->register_handler();
        }
    }
    private static function register_custom_intervals() : void
    {
        if (self::$has_registered_custom_intervals) {
            return;
        }
        \add_filter('cron_schedules', function ($schedules) {
            $schedules['monthly'] = ['interval' => \MONTH_IN_SECONDS, 'display' => \esc_html__('Once a Month', 'independent-analytics')];
            $schedules['five_minutes'] = ['interval' => 300, 'display' => \esc_html__('Every 5 minutes', 'independent-analytics')];
            return $schedules;
        });
        self::$has_registered_custom_intervals = \true;
    }
}
