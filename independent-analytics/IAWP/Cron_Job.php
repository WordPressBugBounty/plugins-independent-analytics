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
    public function schedule()
    {
        $this->unschedule();
        \wp_schedule_event(\time() + 2, $this->interval, $this->name);
    }
    public function unschedule()
    {
        $scheduled_at_timestamp = \wp_next_scheduled($this->name);
        if (\is_int($scheduled_at_timestamp)) {
            \wp_unschedule_event($scheduled_at_timestamp, $this->name);
        }
    }
    public function should_execute_handler() : bool
    {
        return \true;
    }
}
