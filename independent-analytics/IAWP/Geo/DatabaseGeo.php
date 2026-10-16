<?php

namespace IAWP\Geo;

use IAWPSCOPED\MaxMind\Db\Reader;
/** @internal */
class DatabaseGeo
{
    public static function get(string $ip) : ?\IAWP\Geo\Geo
    {
        try {
            $reader = new Reader(\IAWP\Geo\GeoDatabaseManager::path_to_database());
            $geo = $reader->get($ip);
            $reader->close();
        } catch (\Throwable $e) {
            return null;
        }
        if (!\is_array($geo)) {
            return null;
        }
        return \IAWP\Geo\Geo::from_values(self::string_or_null($geo['continent']['names']['en'] ?? null), self::string_or_null($geo['country']['iso_code'] ?? null), self::string_or_null($geo['country']['names']['en'] ?? null), self::string_or_null($geo['city']['names']['en'] ?? null), self::string_or_null($geo['subdivisions'][0]['names']['en'] ?? null));
    }
    private static function string_or_null($value) : ?string
    {
        return \is_string($value) ? $value : null;
    }
}
