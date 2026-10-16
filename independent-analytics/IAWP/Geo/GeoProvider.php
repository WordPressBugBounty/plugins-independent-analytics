<?php

namespace IAWP\Geo;

/** @internal */
class GeoProvider
{
    public const DATABASE = 'database';
    public const CLOUDFLARE = 'cloudflare';
    public static function provider() : string
    {
        $provider = \get_option('iawp_geo_provider', self::DATABASE);
        return self::sanitize($provider);
    }
    public static function is_cloudflare() : bool
    {
        return self::provider() === self::CLOUDFLARE;
    }
    public static function sanitize($provider) : string
    {
        if (!\is_string($provider) || !\in_array($provider, [self::DATABASE, self::CLOUDFLARE], \true)) {
            return self::DATABASE;
        }
        return $provider;
    }
    public static function is_geo_tracking_disabled() : bool
    {
        return \defined('IAWP_DISABLE_GEO_TRACKING') && \IAWP_DISABLE_GEO_TRACKING === \true;
    }
}
