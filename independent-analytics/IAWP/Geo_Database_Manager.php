<?php

namespace IAWP;

use IAWPSCOPED\Carbon\CarbonImmutable;
use Throwable;
use ZipArchive;
/** @internal */
class Geo_Database_Manager
{
    // 🚨🚨 Updating the database? Follow the wiki: 🚨🚨
    // https://github.com/andrewjmead/independent-analytics/wiki/Update-the-Geo-Database
    private $zip_download_url = 'https://assets.independentwp.com/iawp-geo-db-7.mmdb.zip';
    private $raw_download_url = 'https://assets.independentwp.com/iawp-geo-db-7.mmdb';
    private $database_checksum = 'e26ab675eccee3de08e4cd2aceb5a217';
    public function check_database_situation() : void
    {
        if ($this->is_geo_tracking_enabled()) {
            if ($this->has_attempt_interval_elapsed() && !$this->is_downloading() && !$this->is_database_valid()) {
                // Set the options timestamp so the background job doesn't fire multiple times
                $this->record_attempt();
                // Dispatch job
                $background_job = new \IAWP\Geo_Database_Background_Job();
                $background_job->dispatch();
            }
        } else {
            if ($this->is_database_valid()) {
                $this->delete_database();
            }
        }
    }
    public function is_geo_tracking_enabled() : bool
    {
        // Have they disabled geo tracking in wp-config.php?
        if (\defined('IAWP_DISABLE_GEO_TRACKING') && \IAWP_DISABLE_GEO_TRACKING === \true) {
            return \false;
        }
        return \true;
    }
    public function download_database() : void
    {
        \update_option('iawp_is_database_downloading', '1', \true);
        $this->record_attempt();
        $success = $this->download_zip_database_and_extract();
        if (!$success) {
            $this->download_raw_database();
        }
        \update_option('iawp_is_database_downloading', '0', \true);
    }
    public function is_downloading() : bool
    {
        return \get_option('iawp_is_database_downloading', '0') === '1';
    }
    public function delete_database() : void
    {
        \wp_delete_file(self::path_to_database_zip());
        \wp_delete_file(self::path_to_database());
    }
    public function record_attempt() : void
    {
        \update_option('iawp_geo_database_download_last_attempted_at', \time(), \true);
    }
    private function download_zip_database_and_extract() : bool
    {
        $response = \wp_remote_get($this->zip_download_url, ['stream' => \true, 'filename' => self::path_to_database_zip(), 'timeout' => 60]);
        if (\is_wp_error($response)) {
            if (\file_exists(self::path_to_database_zip())) {
                \unlink(self::path_to_database_zip());
            }
            return \false;
        }
        try {
            $zip = new ZipArchive();
            if ($zip->open(self::path_to_database_zip()) === \true) {
                $zip->extractTo(\IAWPSCOPED\iawp_upload_path_to('', \true));
                $zip->close();
            }
        } catch (Throwable $e) {
            // It's ok to fail
        }
        \wp_delete_file(self::path_to_database_zip());
        return $this->is_database_valid();
    }
    private function download_raw_database() : bool
    {
        $response = \wp_remote_get($this->raw_download_url, ['stream' => \true, 'filename' => self::path_to_database(), 'timeout' => 60]);
        if (\is_wp_error($response)) {
            if (\file_exists(self::path_to_database())) {
                \unlink(self::path_to_database());
            }
            return \false;
        }
        return $this->is_database_valid();
    }
    private function is_database_valid() : bool
    {
        if (!\file_exists(self::path_to_database())) {
            return \false;
        }
        try {
            return \verify_file_md5(self::path_to_database(), $this->database_checksum);
        } catch (Throwable $e) {
            return \false;
        }
    }
    private function has_attempt_interval_elapsed() : bool
    {
        $last_attempted_at = $this->last_attempted_at();
        if (\is_null($last_attempted_at)) {
            return \true;
        }
        return $last_attempted_at->addDay()->isPast();
    }
    private function last_attempted_at() : ?CarbonImmutable
    {
        $timestamp = \get_option('iawp_geo_database_download_last_attempted_at', null);
        $valid = \is_int($timestamp) || \is_string($timestamp) && \ctype_digit($timestamp);
        if (!$valid) {
            return null;
        }
        try {
            return CarbonImmutable::createFromTimestamp($timestamp);
        } catch (Throwable $e) {
            return null;
        }
    }
    public static function path_to_database() : string
    {
        return \IAWPSCOPED\iawp_upload_path_to('iawp-geo-db.mmdb', \true);
    }
    private static function path_to_database_zip() : string
    {
        return \IAWPSCOPED\iawp_upload_path_to('iawp-geo-db.zip', \true);
    }
}
