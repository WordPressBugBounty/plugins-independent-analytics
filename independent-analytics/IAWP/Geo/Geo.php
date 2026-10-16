<?php

namespace IAWP\Geo;

/** @internal */
class Geo
{
    private $continent;
    private $country_code;
    private $country;
    private $city;
    private $subdivision;
    private function __construct(string $continent, string $country_code, string $country, string $city, ?string $subdivision)
    {
        $this->continent = $continent;
        $this->country_code = $country_code;
        $this->country = $country;
        $this->city = $city;
        $this->subdivision = $subdivision;
    }
    public function country_code() : string
    {
        return $this->country_code;
    }
    public function city() : string
    {
        return $this->city;
    }
    public function subdivision() : ?string
    {
        return $this->subdivision;
    }
    public function country() : string
    {
        return $this->country;
    }
    public function continent() : string
    {
        return $this->continent;
    }
    public static function get(string $ip) : ?self
    {
        if (\IAWP\Geo\GeoProvider::is_geo_tracking_disabled()) {
            return null;
        }
        if (\IAWP\Geo\GeoProvider::is_cloudflare()) {
            return \IAWP\Geo\CloudflareGeo::get();
        }
        return \IAWP\Geo\DatabaseGeo::get($ip);
    }
    public static function from_values(?string $continent, ?string $country_code, ?string $country, ?string $city, ?string $subdivision) : ?self
    {
        if (!\is_null($city)) {
            $city = self::strip_city_neighborhood_data($city);
        }
        if (!\is_null($country_code)) {
            $country_code = \strtoupper($country_code);
        }
        $required_values = [$continent, $country_code, $country, $city];
        if (\in_array(null, $required_values, \true) || \in_array('', $required_values, \true)) {
            return null;
        }
        return new self($continent, $country_code, $country, $city, $subdivision);
    }
    /**
     * City names can occasionally contain neighborhood data in parentheses after the city name such
     * as "Philadelphia (North Philadelphia)" in of "Philadelphia". This strips that extra
     * neighborhood data, returning just the city name.
     *
     * @param $city_name
     *
     * @return string
     */
    private static function strip_city_neighborhood_data(string $city_name) : string
    {
        $position = \strpos($city_name, '(');
        if ($position !== \false) {
            $city_name = \substr($city_name, 0, $position);
        }
        return \trim($city_name);
    }
}
