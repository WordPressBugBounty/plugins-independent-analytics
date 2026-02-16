<?php

namespace IAWP\Statistics;

use IAWP\Date_Range\Date_Range;
use IAWP\Illuminate_Builder;
use IAWP\Query_Taps;
use IAWP\Rows\Rows;
use IAWP\Utils\Timezone;
use IAWPSCOPED\Illuminate\Database\Query\Builder;
/** @internal */
class Click_Statistics extends \IAWP\Statistics\Statistics
{
    public function total_number_of_rows() : ?int
    {
        $query = Illuminate_Builder::new()->selectRaw('COUNT(DISTINCT links.id) AS total_table_rows')->from($this->tables::links(), 'links')->leftJoin($this->tables::link_rules() . ' AS link_rules', 'link_rules.link_rule_id', '=', 'links.link_rule_id')->leftJoin($this->tables::click_targets() . ' AS click_targets', 'click_targets.click_target_id', '=', 'links.click_target_id')->leftJoin($this->tables::clicked_links() . ' AS clicked_links', 'clicked_links.link_id', '=', 'links.id')->leftJoin($this->tables::clicks() . ' AS clicks', 'clicks.click_id', '=', 'clicked_links.click_id')->leftJoin($this->tables::views() . ' AS views', 'views.id', '=', 'clicks.view_id')->whereBetween('clicks.created_at', [$this->date_range->iso_start(), $this->date_range->iso_end()])->when(!\is_null($this->rows), function (Builder $query) {
            $this->rows->attach_filters($query);
        })->tap(Query_Taps::tap_authored_content_for_clicks());
        return $query->value('total_table_rows');
    }
    protected function make_statistic_instances() : array
    {
        return [$this->make_statistic(['id' => 'clicks', 'name' => \__('Clicks', 'independent-analytics'), 'plugin_group' => 'general']), $this->make_statistic(['id' => 'visitors', 'name' => \__('Visitors', 'independent-analytics'), 'plugin_group' => 'general', 'is_invisible' => \true]), $this->make_statistic(['id' => 'views', 'name' => \__('Views', 'independent-analytics'), 'plugin_group' => 'general', 'is_invisible' => \true]), $this->make_statistic(['id' => 'sessions', 'name' => \__('Sessions', 'independent-analytics'), 'plugin_group' => 'general', 'is_invisible' => \true])];
    }
    protected function query_chart_with_aggregates(Date_Range $current_range, ?Rows $rows = null) : array
    {
        $previous_range = $current_range->previous_period();
        $combined_range = [\min($previous_range->iso_start(), $current_range->iso_start()), \max($previous_range->iso_end(), $current_range->iso_end())];
        $cur_start = $current_range->iso_start();
        $cur_end = $current_range->iso_end();
        $prev_start = $previous_range->iso_start();
        $prev_end = $previous_range->iso_end();
        $is_current = "clicks.created_at BETWEEN '{$cur_start}' AND '{$cur_end}'";
        $is_previous = "clicks.created_at BETWEEN '{$prev_start}' AND '{$prev_end}'";
        $query = Illuminate_Builder::new()->selectRaw("COUNT(DISTINCT CASE WHEN {$is_current} THEN clicks.click_id END) AS cur_clicks")->selectRaw("COUNT(DISTINCT CASE WHEN {$is_current} THEN views.id END) AS cur_views")->selectRaw("COUNT(DISTINCT CASE WHEN {$is_current} THEN sessions.session_id END) AS cur_sessions")->selectRaw("COUNT(DISTINCT CASE WHEN {$is_current} THEN sessions.visitor_id END) AS cur_visitors")->selectRaw("COUNT(DISTINCT CASE WHEN {$is_previous} THEN clicks.click_id END) AS prev_clicks")->selectRaw("COUNT(DISTINCT CASE WHEN {$is_previous} THEN views.id END) AS prev_views")->selectRaw("COUNT(DISTINCT CASE WHEN {$is_previous} THEN sessions.session_id END) AS prev_sessions")->selectRaw("COUNT(DISTINCT CASE WHEN {$is_previous} THEN sessions.visitor_id END) AS prev_visitors")->from($this->tables::clicks(), 'clicks')->leftJoin($this->tables::clicked_links() . ' AS clicked_links', 'clicked_links.click_id', '=', 'clicks.click_id')->leftJoin($this->tables::links() . ' AS links', 'links.id', '=', 'clicked_links.link_id')->leftJoin($this->tables::link_rules() . ' AS link_rules', 'link_rules.link_rule_id', '=', 'links.link_rule_id')->leftJoin($this->tables::click_targets() . ' AS click_targets', 'click_targets.click_target_id', '=', 'links.click_target_id')->leftJoin($this->tables::views() . ' AS views', 'views.id', '=', 'clicks.view_id')->leftJoin($this->tables::sessions() . ' AS sessions', 'sessions.session_id', '=', 'views.session_id')->when(!\is_null($rows), function (Builder $query) use($rows) {
            $rows->attach_filters($query);
        })->tap(Query_Taps::tap_authored_content_for_clicks())->whereBetween('clicks.created_at', $combined_range);
        // Add date interval grouping using clicks.created_at (not sessions.created_at)
        $this->add_click_date_interval_grouping($query);
        $query->groupByRaw("date WITH ROLLUP");
        $all_rows = $query->get()->all();
        // Separate the rollup summary row (date IS NULL) from the per-date chart rows
        $rollup_row = null;
        $chart_rows = [];
        foreach ($all_rows as $row) {
            if (\is_null($row->date)) {
                $rollup_row = $row;
            } else {
                $chart_rows[] = $row;
            }
        }
        // Extract current/previous aggregates from the rollup row
        $current = $this->extract_period_from_combined_row($rollup_row, 'cur_');
        $previous = $this->extract_period_from_combined_row($rollup_row, 'prev_');
        // Extract chart data from the per-date rows (current period only, strip cur_ prefix)
        $chart = \array_map(function (object $row) : object {
            $chart_row = $this->extract_period_from_combined_row($row, 'cur_');
            $chart_row->date = $row->date;
            return $this->clean_up_raw_statistic_row($chart_row);
        }, $chart_rows);
        return ['current' => $this->clean_up_raw_statistic_row($current), 'previous' => $this->clean_up_raw_statistic_row($previous), 'chart' => $chart];
    }
    protected function query(Date_Range $range, ?Rows $rows = null, bool $is_grouped_by_date_interval = \false)
    {
        $query = Illuminate_Builder::new()->selectRaw('COUNT(DISTINCT clicks.click_id) AS clicks')->selectRaw('COUNT(DISTINCT views.id) AS views')->selectRaw('COUNT(DISTINCT sessions.session_id) AS sessions')->selectRaw('COUNT(DISTINCT sessions.visitor_id) AS visitors')->from($this->tables::clicks(), 'clicks')->leftJoin($this->tables::clicked_links() . ' AS clicked_links', 'clicked_links.click_id', '=', 'clicks.click_id')->leftJoin($this->tables::links() . ' AS links', 'links.id', '=', 'clicked_links.link_id')->leftJoin($this->tables::link_rules() . ' AS link_rules', 'link_rules.link_rule_id', '=', 'links.link_rule_id')->leftJoin($this->tables::click_targets() . ' AS click_targets', 'click_targets.click_target_id', '=', 'links.click_target_id')->leftJoin($this->tables::views() . ' AS views', 'views.id', '=', 'clicks.view_id')->leftJoin($this->tables::sessions() . ' AS sessions', 'sessions.session_id', '=', 'views.session_id')->when(!\is_null($rows), function (Builder $query) use($rows) {
            $rows->attach_filters($query);
        })->tap(Query_Taps::tap_authored_content_for_clicks())->whereBetween('clicks.created_at', [$range->iso_start(), $range->iso_end()])->when($is_grouped_by_date_interval, function (Builder $query) {
            $this->add_click_date_interval_grouping($query);
            $query->groupByRaw("date");
        });
        $results = \array_map(function (object $statistic) : object {
            return $this->clean_up_raw_statistic_row($statistic);
        }, $query->get()->all());
        if (!$is_grouped_by_date_interval) {
            return $results[0];
        }
        return $results;
    }
    private function add_click_date_interval_grouping(Builder $query) : void
    {
        $utc_offset = Timezone::utc_offset();
        $site_offset = Timezone::site_offset();
        if ($this->chart_interval->id() === 'daily') {
            $query->selectRaw("DATE(CONVERT_TZ(clicks.created_at, '{$utc_offset}', '{$site_offset}')) AS date");
        } elseif ($this->chart_interval->id() === 'monthly') {
            $query->selectRaw("DATE_FORMAT(CONVERT_TZ(clicks.created_at, '{$utc_offset}', '{$site_offset}'), '%Y-%m-01 00:00:00') AS date");
        } elseif ($this->chart_interval->id() === 'weekly') {
            $day_of_week = \IAWPSCOPED\iawp()->get_option('iawp_dow', 0) + 1;
            $query->selectRaw("\n                       IF (\n                          DAYOFWEEK(CONVERT_TZ(clicks.created_at, '{$utc_offset}', '{$site_offset}')) - {$day_of_week} < 0,\n                          DATE_FORMAT(SUBDATE(CONVERT_TZ(clicks.created_at, '{$utc_offset}', '{$site_offset}'), DAYOFWEEK(CONVERT_TZ(clicks.created_at, '{$utc_offset}', '{$site_offset}')) - {$day_of_week} + 7), '%Y-%m-%d 00:00:00'),\n                          DATE_FORMAT(SUBDATE(CONVERT_TZ(clicks.created_at, '{$utc_offset}', '{$site_offset}'), DAYOFWEEK(CONVERT_TZ(clicks.created_at, '{$utc_offset}', '{$site_offset}')) - {$day_of_week}), '%Y-%m-%d 00:00:00')\n                       ) AS date\n                   ");
        } else {
            $query->selectRaw("DATE_FORMAT(CONVERT_TZ(clicks.created_at, '{$utc_offset}', '{$site_offset}'), '%Y-%m-%d %H:00:00') AS date");
        }
    }
}
