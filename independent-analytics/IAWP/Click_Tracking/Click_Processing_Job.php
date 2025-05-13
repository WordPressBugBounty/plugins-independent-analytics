<?php

namespace IAWP\Click_Tracking;

use IAWP\Cron_Job;
use IAWP\Models\Visitor;
use IAWP\Payload_Validator;
use IAWP\Utils\Security;
use IAWPSCOPED\Illuminate\Support\Str;
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
        $click_data_file = $this->get_click_data_file();
        if ($click_data_file === null) {
            return;
        }
        $job_file = $this->create_job_file($click_data_file);
        if ($job_file === null) {
            return;
        }
        $job_handle = \fopen($job_file, 'r');
        if ($job_handle === \false) {
            return;
        }
        // The first line for the PHP file is an exit statement to keep the contents private. This
        // should be skipped when parsing the file.
        if (\pathinfo($job_file, \PATHINFO_EXTENSION) === 'php') {
            \fgets($job_handle);
            // Skip first line
        }
        while (($json = \fgets($job_handle)) !== \false) {
            $event = \json_decode($json, \true);
            $event['href'] = Security::string($event['href']);
            $event['classes'] = Security::string($event['classes']);
            $event['ids'] = Security::string($event['ids']);
            if (\is_null($event)) {
                continue;
            }
            $payload_validator = Payload_Validator::new($event['payload'], $event['signature']);
            if (!$payload_validator->is_valid() || \is_null($payload_validator->resource())) {
                continue;
            }
            $click = \IAWP\Click_Tracking\Click::new(['href' => $event['href'], 'classes' => $event['classes'], 'ids' => $event['ids'], 'resource_id' => $payload_validator->resource()['id'], 'visitor_id' => Visitor::fetch_visitor_id_by_hash($event['visitor_token']), 'created_at' => \DateTime::createFromFormat('U', $event['created_at'])]);
            $click->track();
        }
        \fclose($job_handle);
        \unlink($job_file);
    }
    private function get_click_data_file() : ?string
    {
        $text_file = Str::finish(\sys_get_temp_dir(), \DIRECTORY_SEPARATOR) . "iawp-click-data.txt";
        if (\is_file($text_file) && \is_readable($text_file) && \is_writable($text_file)) {
            return $text_file;
        }
        $php_file = \IAWPSCOPED\iawp_path_to('iawp-click-data.php');
        if (\is_file($php_file)) {
            return $php_file;
        }
        return null;
    }
    private function create_job_file(string $file) : ?string
    {
        if (!\is_readable($file) || !\is_writable($file)) {
            return null;
        }
        $job_id = \rand();
        $extension = \pathinfo($file, \PATHINFO_EXTENSION);
        $job_file = Str::finish(\dirname($file), \DIRECTORY_SEPARATOR) . "iawp-click-data-{$job_id}.{$extension}";
        $was_renamed = \rename($file, $job_file);
        if (!$was_renamed || !\is_file($job_file)) {
            return null;
        }
        return $job_file;
    }
}
