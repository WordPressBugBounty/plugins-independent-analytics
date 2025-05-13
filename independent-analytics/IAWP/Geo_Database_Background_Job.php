<?php

namespace IAWP;

use IAWP\Utils\WP_Async_Request;
/** @internal */
class Geo_Database_Background_Job extends WP_Async_Request
{
    /**
     * @var string
     */
    protected $action = 'iawp_geo_database_background_job';
    /**
     * Handle
     *
     * Override this method to perform any actions required
     * during the async request.
     *
     * @return void
     */
    protected function handle() : void
    {
        $downloader = new \IAWP\Geo_Database_Manager();
        $downloader->download_database();
    }
}
