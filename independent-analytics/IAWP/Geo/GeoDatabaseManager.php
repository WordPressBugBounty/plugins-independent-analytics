<?php

namespace IAWP\Geo;

use Throwable;
use ZipArchive;
/** @internal */
class GeoDatabaseManager
{
    // Injected from geo-database.json by gulp. Run `npm run update-geo-db` to publish a new database.
    private const VERSION = '202610';
    private const DATABASE_CHECKSUM = '5966907981d3e82f338e823d1b83e002';
    private const RAW_DOWNLOAD_URL = 'https://assets.independentwp.com/iawp-geo-db-' . self::VERSION . '.mmdb';
    private const ZIP_DOWNLOAD_URL = self::RAW_DOWNLOAD_URL . '.zip';
    public static function sync() : void
    {
        if (self::is_geo_tracking_disabled()) {
            self::delete_database();
            return;
        }
        if (\IAWP\Geo\GeoProvider::is_cloudflare() && !\is_multisite()) {
            self::delete_database();
            return;
        }
        if (self::is_database_valid()) {
            return;
        }
        self::download_database();
    }
    public static function delete_database() : bool
    {
        \wp_delete_file(self::path_to_database_zip());
        \wp_delete_file(self::path_to_database());
        return !\file_exists(self::path_to_database_zip()) && !\file_exists(self::path_to_database());
    }
    public static function path_to_database() : string
    {
        return \IAWPSCOPED\iawp_upload_path_to('iawp-geo-db.mmdb', \true);
    }
    private static function download_database() : void
    {
        self::delete_database();
        $success = self::download_zip_database_and_extract();
        if (!$success) {
            self::download_raw_database();
        }
    }
    private static function is_database_valid() : bool
    {
        if (!\file_exists(self::path_to_database())) {
            return \false;
        }
        $calculated_checksum = \md5_file(self::path_to_database());
        if ($calculated_checksum !== self::DATABASE_CHECKSUM) {
            return \false;
        }
        return \true;
    }
    private static function is_geo_tracking_disabled() : bool
    {
        return \IAWP\Geo\GeoProvider::is_geo_tracking_disabled();
    }
    private static function download_zip_database_and_extract() : bool
    {
        $response = \wp_remote_get(self::ZIP_DOWNLOAD_URL, ['stream' => \true, 'filename' => self::path_to_database_zip(), 'timeout' => 60]);
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
        return self::is_database_valid();
    }
    private static function download_raw_database() : bool
    {
        $response = \wp_remote_get(self::RAW_DOWNLOAD_URL, ['stream' => \true, 'filename' => self::path_to_database(), 'timeout' => 60]);
        if (\is_wp_error($response)) {
            if (\file_exists(self::path_to_database())) {
                \unlink(self::path_to_database());
            }
            return \false;
        }
        return self::is_database_valid();
    }
    private static function path_to_database_zip() : string
    {
        return \IAWPSCOPED\iawp_upload_path_to('iawp-geo-db.zip', \true);
    }
}
