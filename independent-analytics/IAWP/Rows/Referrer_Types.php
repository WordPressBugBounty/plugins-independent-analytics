<?php

namespace IAWP\Rows;

use IAWP\Form_Submissions\Form;
use IAWP\Illuminate_Builder;
use IAWP\Models\Referrer_Type;
use IAWP\Query;
use IAWP\Query_Taps;
use IAWP\Tables;
use IAWPSCOPED\Illuminate\Database\Query\Builder;
use IAWPSCOPED\Illuminate\Database\Query\JoinClause;
/** @internal */
class Referrer_Types extends \IAWP\Rows\Rows
{
    public function attach_filters(Builder $query) : void
    {
        $query->joinSub($this->get_filter_query(), 'referrer_type_rows', function (JoinClause $join) {
            $join->on('referrer_type_rows.referrer_type_id', '=', 'referrers.referrer_type_id');
        });
    }
    protected function fetch_rows() : array
    {
        $rows = $this->query()->get()->all();
        return \array_map(function ($row) {
            return new Referrer_Type($row);
        }, $rows);
    }
    protected function sort_tie_breaker_column() : string
    {
        return 'referrer_type';
    }
    protected function query(?bool $skip_pagination = \false) : Builder
    {
        if ($skip_pagination) {
            $this->number_of_rows = null;
        }
        $sessions_table = Query::get_table_name(Query::SESSIONS);
        $referrers_table = Query::get_table_name(Query::REFERRERS);
        $session_statistics = $this->session_statistics_query()->whereNotNull('sessions.referrer_id');
        $referrer_types_query = Illuminate_Builder::new();
        $referrer_types_query->select('referrer_types.id AS referrer_type_id', 'referrer_types.referrer_type')->selectRaw('IFNULL(CAST(SUM(sessions.views) AS SIGNED), 0) AS views')->selectRaw('COUNT(DISTINCT sessions.visitor_id)  AS visitors')->selectRaw('COUNT(DISTINCT sessions.session_id)  AS sessions')->selectRaw('ROUND(AVG( TIMESTAMPDIFF(SECOND, sessions.created_at, sessions.ended_at))) AS average_session_duration')->selectRaw('COUNT(DISTINCT IF(sessions.final_view_id IS NULL, sessions.session_id, NULL))  AS bounces')->selectRaw('SUM(sessions.clicks)  AS clicks')->selectRaw('SUM(sessions.wc_orders) AS wc_orders')->selectRaw('SUM(sessions.wc_gross_sales) AS wc_gross_sales')->selectRaw('SUM(sessions.wc_refunded_amount) AS wc_refunded_amount')->selectRaw('SUM(sessions.wc_refunds) AS wc_refunds')->selectRaw('SUM(sessions.form_submissions) AS form_submissions')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("SUM(sessions.{$form->submissions_column()}) AS {$form->submissions_column()}");
            }
        })->fromSub($session_statistics, 'sessions')->leftJoin($referrer_types_query->raw($referrers_table . ' AS referrers'), function (JoinClause $join) {
            $join->on('sessions.referrer_id', '=', 'referrers.id');
        })->leftJoin($referrer_types_query->raw(Tables::referrer_types() . ' AS referrer_types'), function (JoinClause $join) {
            $join->on('referrers.referrer_type_id', '=', 'referrer_types.id');
        })->when(!$this->appears_to_be_for_real_time_analytics(), function (Builder $query) {
            $query->whereBetween('sessions.created_at', $this->get_current_period_iso_range());
        })->when(\is_int($this->solo_record_id), function (Builder $query) {
            $query->where('referrer_types.id', '=', $this->solo_record_id);
        })->groupBy(['referrers.referrer_type_id'])->having('views', '>', 0)->tap(fn(Builder $query) => $this->apply_record_filters($query))->when($this->can_order_and_limit_at_record_level(), function (Builder $query) {
            $query->tap(fn(Builder $query) => $this->apply_order_and_limit($query, $this->sort_configuration->column()));
        });
        $previous_period_query = Illuminate_Builder::new();
        $previous_period_query->select(['referrers.referrer_type_id AS referrer_type_id'])->selectRaw('SUM(sessions.total_views) AS previous_period_views')->selectRaw('COUNT(DISTINCT sessions.visitor_id) AS previous_period_visitors')->from($sessions_table, 'sessions')->leftJoin($referrer_types_query->raw($referrers_table . ' AS referrers'), function (JoinClause $join) {
            $join->on('sessions.referrer_id', '=', 'referrers.id');
        })->tap(Query_Taps::tap_related_to_examined_record_for_previous_period($this->examiner_config, ['sessions', 'referrers']))->whereBetween('sessions.created_at', $this->get_previous_period_iso_range())->groupBy(['referrers.referrer_type_id']);
        $outer_query = Illuminate_Builder::new();
        $outer_query->selectRaw('referrer_types.*')->selectRaw('IF(sessions = 0, 0, views / sessions) AS views_per_session')->selectRaw('IFNULL((views - previous_period_views) / previous_period_views * 100, 0) AS views_growth')->selectRaw('IFNULL((visitors - previous_period_visitors) / previous_period_visitors * 100, 0) AS visitors_growth')->selectRaw('IFNULL(bounces / sessions * 100, 0) AS bounce_rate')->selectRaw('ROUND(CAST(wc_gross_sales - wc_refunded_amount AS SIGNED)) AS wc_net_sales')->selectRaw('IF(visitors = 0, 0, (wc_orders / visitors) * 100) AS wc_conversion_rate')->selectRaw('IF(visitors = 0, 0, (wc_gross_sales - wc_refunded_amount) / visitors) AS wc_earnings_per_visitor')->selectRaw('IF(wc_orders = 0, 0, ROUND(CAST(wc_gross_sales / wc_orders AS SIGNED))) AS wc_average_order_volume')->selectRaw('IF(visitors = 0, 0, (form_submissions / visitors) * 100) AS form_conversion_rate')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("IF(visitors = 0, 0, ({$form->submissions_column()} / visitors) * 100) AS {$form->conversion_rate_column()}");
            }
        })->fromSub($referrer_types_query, 'referrer_types')->leftJoinSub($previous_period_query, 'previous_period_stats', 'referrer_types.referrer_type_id', '=', 'previous_period_stats.referrer_type_id')->tap(fn(Builder $query) => $this->apply_aggregate_filters($query))->when(!$this->can_order_and_limit_at_record_level() && !($this->using_logical_or_operator() && $this->filtering_by_mixed_columns()), function (Builder $query) {
            $query->tap(fn(Builder $query) => $this->apply_order_and_limit($query, $this->sort_configuration->column()));
        });
        if ($this->using_logical_or_operator() && $this->filtering_by_mixed_columns()) {
            $og_outer_query = $outer_query;
            $outer_query = Illuminate_Builder::new()->select('*')->fromSub($og_outer_query, 'records')->tap(fn(Builder $query) => $this->apply_or_filters($query))->tap(fn(Builder $query) => $this->apply_order_and_limit($query, $this->sort_configuration->column()));
        }
        return $outer_query;
    }
}
