<?php

namespace IAWP;

/** @internal */
abstract class Cron_Job
{
    protected $name = '';
    protected $interval = 'daily';
    public abstract function handle() : void;
    public function register_handler() : void
    {
        \add_action($this->name, function () {
            if ($this->should_execute_handler()) {
                $this->handle();
            }
        });
    }
    public function unschedule()
    {
        $scheduled_at_timestamp = \wp_next_scheduled($this->name);
        if (\is_int($scheduled_at_timestamp)) {
            \wp_unschedule_event($scheduled_at_timestamp, $this->name);
        }
    }
    public function schedule()
    {
        $scheduled_at_timestamp = \wp_next_scheduled($this->name);
        if (!\is_int($scheduled_at_timestamp)) {
            \wp_schedule_event(\time() + 2, $this->interval, $this->name);
        }
    }
    public function should_execute_handler() : bool
    {
        return \true;
    }
    public static function register_custom_intervals() : void
    {
        \add_filter('cron_schedules', function ($schedules) {
            $schedules['monthly'] = ['interval' => \MONTH_IN_SECONDS, 'display' => \esc_html__('Once a Month', 'independent-analytics')];
            $schedules['five_minutes'] = ['interval' => 300, 'display' => \esc_html__('Every 5 minutes', 'independent-analytics')];
            $schedules['every_minute'] = ['interval' => 60, 'display' => \esc_html__('Every minute', 'independent-analytics')];
            return $schedules;
        });
    }
}
