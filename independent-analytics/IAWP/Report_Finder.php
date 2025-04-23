<?php

namespace IAWP;

use IAWPSCOPED\Illuminate\Support\Collection;
/** @internal */
class Report_Finder
{
    /** @var Report[] $cached_reports */
    private static $cached_reports;
    /** @var Report[] $get_reports_cached */
    private static $get_reports_cached;
    public function __construct()
    {
        $this->sync_cache();
    }
    /**
     * @return Report[]
     */
    public function get_reports() : array
    {
        if (\is_array(self::$get_reports_cached)) {
            return self::$get_reports_cached;
        }
        $reports = [];
        $reports[] = \IAWP\Report_Finder::get_base_report_for_type('views');
        $reports = \array_merge($reports, $this->get_saved_reports_for_type('views'));
        $reports[] = \IAWP\Report_Finder::get_base_report_for_type('referrers');
        $reports = \array_merge($reports, $this->get_saved_reports_for_type('referrers'));
        $reports[] = \IAWP\Report_Finder::get_base_report_for_type('geo');
        $reports = \array_merge($reports, $this->get_saved_reports_for_type('geo'));
        $reports[] = \IAWP\Report_Finder::get_base_report_for_type('devices');
        $reports = \array_merge($reports, $this->get_saved_reports_for_type('devices'));
        $reports[] = \IAWP\Report_Finder::get_base_report_for_type('campaigns');
        $reports = \array_merge($reports, $this->get_saved_reports_for_type('campaigns'));
        $reports[] = \IAWP\Report_Finder::get_base_report_for_type('clicks');
        $reports = \array_merge($reports, $this->get_saved_reports_for_type('clicks'));
        self::$get_reports_cached = $reports;
        return $reports;
    }
    public function get_reports_grouped_by_type() : array
    {
        return Collection::make($this->get_reports())->groupBy(function (\IAWP\Report $report) {
            return $report->type();
        })->map(function (Collection $collection) {
            return ['base_report' => $collection->first(), 'saved_reports' => $collection->slice(1)];
        })->all();
    }
    /**
     * @param string $type
     *
     * @return Report[]
     */
    public function get_saved_reports_for_type(string $type) : array
    {
        return \array_filter(self::$cached_reports, function (\IAWP\Report $report) use($type) {
            return $report->type() === $type;
        });
    }
    /**
     * Fetch the current report based on the pages query parameters.
     *
     * @return Report|null
     */
    public function fetch_current_report() : ?\IAWP\Report
    {
        $report_id = \array_key_exists('report', $_GET) ? \sanitize_text_field($_GET['report']) : null;
        if ($report_id === null) {
            $report_id = \IAWP\Env::get_tab();
        }
        if ($report_id === null) {
            return null;
        }
        return $this->fetch_report($report_id);
    }
    /**
     * @param string|int $report_id The report id can be a saved report id like 1 or a base report id like 'views'
     *
     * @return Report|null
     */
    public function fetch_report($report_id) : ?\IAWP\Report
    {
        $report = $this->fetch_report_by_id($report_id);
        if ($report instanceof \IAWP\Report) {
            return $report;
        }
        $report = self::get_base_report_for_type($report_id);
        if ($report instanceof \IAWP\Report) {
            return $report;
        }
        return null;
    }
    /**
     * Get a basic base report for a given report type.
     *
     * @param string $type
     *
     * @return Report|null
     */
    public function get_base_report_for_type(string $type) : ?\IAWP\Report
    {
        switch ($type) {
            case 'views':
                return new \IAWP\Report((object) ['report_id' => 'views', 'name' => \esc_html__('Pages', 'independent-analytics'), 'type' => 'views']);
            case 'referrers':
                return new \IAWP\Report((object) ['report_id' => 'referrers', 'name' => \esc_html__('Referrers', 'independent-analytics'), 'type' => 'referrers']);
            case 'geo':
                return new \IAWP\Report((object) ['report_id' => 'geo', 'name' => \esc_html__('Geographic', 'independent-analytics'), 'type' => 'geo']);
            case 'devices':
                return new \IAWP\Report((object) ['report_id' => 'devices', 'name' => \esc_html__('Devices', 'independent-analytics'), 'type' => 'devices']);
            case 'campaigns':
                return new \IAWP\Report((object) ['report_id' => 'campaigns', 'name' => \esc_html__('Campaigns', 'independent-analytics'), 'type' => 'campaigns']);
            case 'clicks':
                return new \IAWP\Report((object) ['report_id' => 'clicks', 'name' => \esc_html__('Clicks', 'independent-analytics'), 'type' => 'clicks']);
            case 'overview':
                return new \IAWP\Report((object) ['report_id' => 'overview', 'name' => \esc_html__('Overview', 'independent-analytics'), 'type' => 'overview']);
            case 'real-time':
                return new \IAWP\Report((object) ['report_id' => 'real-time', 'name' => \esc_html__('Real-time', 'independent-analytics'), 'type' => 'real-time']);
            default:
                return null;
        }
    }
    /**
     * Fetch a report from the database by its id.
     *
     * @param string|int $id
     *
     * @return Report|null
     */
    public function fetch_report_by_id($id) : ?\IAWP\Report
    {
        // TODO This should rely on the cache, but for now there are other classes manipulating the
        //  reports table, so it's best to just fetch a fresh copy from the databae.
        $id = (string) $id;
        $reports_table = \IAWP\Query::get_table_name(\IAWP\Query::REPORTS);
        if (!\ctype_digit($id)) {
            return null;
        }
        $row = \IAWP\Illuminate_Builder::new()->from($reports_table)->where('report_id', '=', $id)->first();
        if (\is_null($row)) {
            return null;
        }
        return new \IAWP\Report($row);
    }
    public function get_favorited_report() : ?\IAWP\Report
    {
        $raw_id = \get_user_meta(\get_current_user_id(), 'iawp_favorite_report_id', \true);
        $id = \filter_var($raw_id, \FILTER_VALIDATE_INT);
        if (\is_int($id)) {
            return $this->fetch_report_by_id($id);
        }
        $raw_type = \get_user_meta(\get_current_user_id(), 'iawp_favorite_report_type', \true);
        $type = \filter_var($raw_type, \FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        return self::get_base_report_for_type($type);
    }
    public function insert_report(array $attributes) : \IAWP\Report
    {
        $reports_table = \IAWP\Query::get_table_name(\IAWP\Query::REPORTS);
        if (\array_key_exists('columns', $attributes) && \is_array($attributes['columns'])) {
            $attributes['columns'] = \json_encode($attributes['columns']);
        }
        if (\array_key_exists('filters', $attributes) && \is_array($attributes['filters'])) {
            $attributes['filters'] = \json_encode($attributes['filters']);
        }
        $report_id = \IAWP\Illuminate_Builder::new()->from($reports_table)->insertGetId($attributes);
        $this->sync_cache(\true);
        return $this->fetch_report_by_id($report_id);
    }
    private function sync_cache(bool $force = \false)
    {
        if (\is_array(self::$cached_reports) && \false === $force) {
            return;
        }
        $reports_table = \IAWP\Query::get_table_name(\IAWP\Query::REPORTS);
        $builder = \IAWP\Illuminate_Builder::new()->from($reports_table)->orderByRaw('position IS NULL')->orderBy('position')->orderBy('report_id')->get()->escapeWhenCastingToString();
        $rows = $builder->toArray();
        self::$cached_reports = \array_map(function ($row) {
            return new \IAWP\Report($row);
        }, $rows);
    }
    public static function new() : self
    {
        return new self();
    }
}
