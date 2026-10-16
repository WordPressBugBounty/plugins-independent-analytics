<?php

namespace IAWP;

use IAWP\Geo\GeoDatabaseManager;
/** @internal */
class Geo_Database_Health_Check_Job extends \IAWP\Cron_Job
{
    protected $name = 'iawp_cron_geo_database_health_check';
    protected $interval = 'daily';
    public function handle() : void
    {
        GeoDatabaseManager::sync();
    }
}
