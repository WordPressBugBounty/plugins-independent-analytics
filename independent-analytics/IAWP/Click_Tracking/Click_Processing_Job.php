<?php

namespace IAWP\Click_Tracking;

use IAWP\Cron_Job;
use IAWP\Models\Visitor;
use IAWP\Payload_Validator;
use IAWP\Utils\Security;
/** @internal */
class Click_Processing_Job extends Cron_Job
{
    protected $name = 'iawp_click_processing';
    protected $interval = 'every_minute';
    public function handle() : void
    {
        // Periodically recreate the config file
        \IAWP\Click_Tracking\Config_File_Manager::recreate();
        if (\IAWPSCOPED\iawp_is_free()) {
            self::unschedule();
            return;
        }
        $job_id = \rand();
        $original_file = \IAWPSCOPED\iawp_path_to('iawp-click-data.php');
        $job_file = \IAWPSCOPED\iawp_path_to("iawp-click-data-{$job_id}.php");
        if (!\is_file($original_file)) {
            return;
        }
        $original_handle = \fopen($original_file, 'r');
        if ($original_handle === \false) {
            return;
        }
        \fclose($original_handle);
        \rename($original_file, $job_file);
        $job_handle = \fopen($job_file, 'r');
        if ($job_handle === \false) {
            return;
        }
        // Skip the first line which is just a php exit; statement
        \fgets($job_handle);
        while (($json = \fgets($job_handle)) !== \false) {
            $event = \json_decode($json, \true);
            $event['href'] = Security::string($event['href']);
            $event['classes'] = Security::string($event['classes']);
            if (\is_null($event)) {
                continue;
            }
            $payload_validator = Payload_Validator::new($event['payload'], $event['signature']);
            if (!$payload_validator->is_valid() || \is_null($payload_validator->resource())) {
                continue;
            }
            $click = \IAWP\Click_Tracking\Click::new(['href' => $event['href'], 'classes' => $event['classes'], 'resource_id' => $payload_validator->resource()['id'], 'visitor_id' => Visitor::fetch_visitor_id_by_hash($event['visitor_token']), 'created_at' => \DateTime::createFromFormat('U', $event['created_at'])]);
            $click->track();
        }
        \fclose($job_handle);
        \unlink($job_file);
    }
}
