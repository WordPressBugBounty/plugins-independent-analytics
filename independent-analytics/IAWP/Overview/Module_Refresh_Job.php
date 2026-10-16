<?php

namespace IAWP\Overview;

use IAWP\Cron_Job;
/** @internal */
class Module_Refresh_Job extends Cron_Job
{
    protected $name = 'iawp_module_refresh';
    protected $interval = 'hourly';
    public function schedule()
    {
        if ($this->is_disabled()) {
            $this->unschedule();
            return;
        }
        parent::schedule();
    }
    public function should_execute_handler() : bool
    {
        return !$this->is_disabled();
    }
    public function handle() : void
    {
        \update_option('iawp_should_refresh_modules', '1', \true);
    }
    private function is_disabled() : bool
    {
        return \defined('IAWP_DISABLE_OVERVIEW_HOURLY_REFRESH') && \IAWP_DISABLE_OVERVIEW_HOURLY_REFRESH === \true;
    }
}
