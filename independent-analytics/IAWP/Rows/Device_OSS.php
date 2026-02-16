<?php

namespace IAWP\Rows;

use IAWP\Form_Submissions\Form;
use IAWP\Illuminate_Builder;
use IAWP\Models\Device;
use IAWP\Query;
use IAWP\Query_Taps;
use IAWPSCOPED\Illuminate\Database\Query\Builder;
use IAWPSCOPED\Illuminate\Database\Query\JoinClause;
/** @internal */
class Device_OSS extends \IAWP\Rows\Rows
{
    public function attach_filters(Builder $query) : void
    {
        $query->joinSub($this->get_filter_query(), 'device_os_rows', function (JoinClause $join) {
            $join->on('device_os_rows.device_os_id', '=', 'sessions.device_os_id');
        });
    }
    protected function fetch_rows() : array
    {
        $rows = $this->query()->get()->all();
        return \array_map(function ($row) {
            return new Device($row);
        }, $rows);
    }
    protected function sort_tie_breaker_column() : string
    {
        return 'os';
    }
    protected function query(?bool $skip_pagination = \false) : Builder
    {
        if ($skip_pagination) {
            $this->number_of_rows = null;
        }
        $sessions_table = Query::get_table_name(Query::SESSIONS);
        $device_oss_table = Query::get_table_name(Query::DEVICE_OSS);
        $session_statistics = $this->session_statistics_query()->whereNotNull('sessions.device_os_id');
        $device_oss_query = Illuminate_Builder::new();
        $device_oss_query->select('device_oss.device_os_id', 'device_oss.device_os AS os')->selectRaw('IFNULL(CAST(SUM(sessions.views) AS SIGNED), 0) AS views')->selectRaw('COUNT(DISTINCT sessions.visitor_id)  AS visitors')->selectRaw('COUNT(DISTINCT sessions.session_id)  AS sessions')->selectRaw('ROUND(AVG( TIMESTAMPDIFF(SECOND, sessions.created_at, sessions.ended_at))) AS average_session_duration')->selectRaw('COUNT(DISTINCT IF(sessions.final_view_id IS NULL, sessions.session_id, NULL))  AS bounces')->selectRaw('SUM(sessions.clicks)  AS clicks')->selectRaw('SUM(sessions.wc_orders) AS wc_orders')->selectRaw('SUM(sessions.wc_gross_sales) AS wc_gross_sales')->selectRaw('SUM(sessions.wc_refunded_amount) AS wc_refunded_amount')->selectRaw('SUM(sessions.wc_refunds) AS wc_refunds')->selectRaw('SUM(sessions.form_submissions) AS form_submissions')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("SUM(sessions.{$form->submissions_column()}) AS {$form->submissions_column()}");
            }
        })->fromSub($session_statistics, 'sessions')->join($device_oss_query->raw($device_oss_table . ' AS device_oss'), function (JoinClause $join) {
            $join->on('sessions.device_os_id', '=', 'device_oss.device_os_id');
        })->when(!$this->appears_to_be_for_real_time_analytics(), function (Builder $query) {
            $query->whereBetween('sessions.created_at', $this->get_current_period_iso_range());
        })->when(\is_int($this->solo_record_id), function (Builder $query) {
            $query->where('device_oss.device_os_id', '=', $this->solo_record_id);
        })->groupBy('device_oss.device_os_id')->having('views', '>', 0)->tap(fn(Builder $query) => $this->apply_record_filters($query))->when($this->can_order_and_limit_at_record_level(), function (Builder $query) {
            $query->tap(fn(Builder $query) => $this->apply_order_and_limit($query, $this->sort_configuration->column()));
        });
        $previous_period_query = Illuminate_Builder::new();
        $previous_period_query->select(['sessions.device_os_id'])->selectRaw('SUM(sessions.total_views) AS previous_period_views')->selectRaw('COUNT(DISTINCT sessions.visitor_id) AS previous_period_visitors')->from($sessions_table, 'sessions')->tap(Query_Taps::tap_related_to_examined_record_for_previous_period($this->examiner_config, ['sessions']))->whereBetween('sessions.created_at', $this->get_previous_period_iso_range())->groupBy('sessions.device_os_id');
        $outer_query = Illuminate_Builder::new();
        $outer_query->selectRaw('device_oss.*')->selectRaw('IF(sessions = 0, 0, views / sessions) AS views_per_session')->selectRaw('IFNULL((views - previous_period_views) / previous_period_views * 100, 0) AS views_growth')->selectRaw('IFNULL((visitors - previous_period_visitors) / previous_period_visitors * 100, 0) AS visitors_growth')->selectRaw('IFNULL(bounces / sessions * 100, 0) AS bounce_rate')->selectRaw('ROUND(CAST(wc_gross_sales - wc_refunded_amount AS SIGNED)) AS wc_net_sales')->selectRaw('IF(visitors = 0, 0, (wc_orders / visitors) * 100) AS wc_conversion_rate')->selectRaw('IF(visitors = 0, 0, (wc_gross_sales - wc_refunded_amount) / visitors) AS wc_earnings_per_visitor')->selectRaw('IF(wc_orders = 0, 0, ROUND(CAST(wc_gross_sales / wc_orders AS SIGNED))) AS wc_average_order_volume')->selectRaw('IF(visitors = 0, 0, (form_submissions / visitors) * 100) AS form_conversion_rate')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("IF(visitors = 0, 0, ({$form->submissions_column()} / visitors) * 100) AS {$form->conversion_rate_column()}");
            }
        })->fromSub($device_oss_query, 'device_oss')->leftJoinSub($previous_period_query, 'previous_period_stats', 'device_oss.device_os_id', '=', 'previous_period_stats.device_os_id')->tap(fn(Builder $query) => $this->apply_aggregate_filters($query))->when(!$this->can_order_and_limit_at_record_level() && !($this->using_logical_or_operator() && $this->filtering_by_mixed_columns()), function (Builder $query) {
            $query->tap(fn(Builder $query) => $this->apply_order_and_limit($query, $this->sort_configuration->column()));
        });
        if ($this->using_logical_or_operator() && $this->filtering_by_mixed_columns()) {
            $og_outer_query = $outer_query;
            $outer_query = Illuminate_Builder::new()->select('*')->fromSub($og_outer_query, 'records')->tap(fn(Builder $query) => $this->apply_or_filters($query))->tap(fn(Builder $query) => $this->apply_order_and_limit($query, $this->sort_configuration->column()));
        }
        return $outer_query;
    }
}
