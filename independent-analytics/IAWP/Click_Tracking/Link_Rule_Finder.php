<?php

namespace IAWP\Click_Tracking;

use IAWP\Illuminate_Builder;
use IAWP\Tables;
use IAWP\Utils\URL;
use IAWPSCOPED\Illuminate\Support\Collection;
// TODO - This for more link a matcher and not a finder...
/** @internal */
class Link_Rule_Finder
{
    private $protocol;
    private $href;
    private $classes;
    private static $database_records = null;
    public function __construct(?string $protocol, ?string $href, string $classes)
    {
        $this->protocol = $protocol;
        $this->href = $href;
        $this->classes = $classes;
    }
    public function links() : ?Collection
    {
        return self::get_database_records()->filter(function ($link_rule) {
            return $link_rule->is_active();
        })->filter(function ($link_rule) {
            return $this->is_match($link_rule);
        })->values();
    }
    private function is_match($link_rule) : bool
    {
        switch ($link_rule->type()) {
            case 'class':
                return $this->is_matching_class($link_rule);
            case 'domain':
                return $this->is_matching_domain($link_rule);
            case 'extension':
                return $this->is_matching_extension($link_rule);
            case 'subdirectory':
                return $this->is_matching_subdirectory($link_rule);
            case 'protocol':
                return $this->is_matching_protocol($link_rule);
            default:
                return \false;
        }
    }
    private function is_matching_class($link_rule) : bool
    {
        if ($this->classes === "") {
            return \false;
        }
        return Collection::make(\explode(' ', $this->classes))->contains(function ($value, $key) use($link_rule) {
            return $link_rule->value() === $value;
        });
    }
    private function is_matching_domain($link_rule) : bool
    {
        if (\is_null($this->href)) {
            return \false;
        }
        $url = new URL($this->href);
        if (!$url->is_valid_url()) {
            return \false;
        }
        return $link_rule->value() === $url->get_domain();
    }
    private function is_matching_extension($link_rule) : bool
    {
        if (\is_null($this->href)) {
            return \false;
        }
        $url = new URL($this->href);
        if (!$url->is_valid_url()) {
            return \false;
        }
        return $link_rule->value() === $url->get_extension();
    }
    private function is_matching_subdirectory($link_rule) : bool
    {
        if (\is_null($this->href)) {
            return \false;
        }
        $url = URL::new($this->href);
        $site_url = URL::new(\get_site_url());
        if (!$url->is_valid_url() || $url->get_domain() !== $site_url->get_domain()) {
            return \false;
        }
        $path = $url->get_path();
        $path_parts = Collection::make(\explode('/', $path))->filter()->values();
        if ($path_parts->isEmpty()) {
            return \false;
        }
        return $link_rule->value() === $path_parts->first();
    }
    private function is_matching_protocol($link_rule) : bool
    {
        if (\is_null($this->href)) {
            return \false;
        }
        return $this->protocol === $link_rule->value();
    }
    public static function new(?string $protocol, ?string $href, string $classes) : self
    {
        return new self($protocol, $href, $classes);
    }
    public static function link_rules() : Collection
    {
        return self::get_database_records();
    }
    public static function active_link_rules() : Collection
    {
        return self::get_database_records()->filter(function ($link_rule) {
            return $link_rule->is_active();
        });
    }
    public static function inactive_link_rules() : Collection
    {
        return self::get_database_records()->filter(function ($link_rule) {
            return !$link_rule->is_active();
        });
    }
    private static function get_database_records() : Collection
    {
        if (\is_null(static::$database_records)) {
            $records = Illuminate_Builder::new()->from(Tables::link_rules())->orderBy('position')->orderByDesc('created_at')->get();
            static::$database_records = $records->map(function ($link_rule) {
                return new \IAWP\Click_Tracking\Link_Rule($link_rule);
            });
        }
        return static::$database_records;
    }
}
