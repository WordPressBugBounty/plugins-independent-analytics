<?php

namespace IAWP\Rows;

use IAWP\Form_Submissions\Form;
use IAWP\Illuminate_Builder;
use IAWP\Models\Campaign;
use IAWP\Query;
use IAWP\Query_Taps;
use IAWP\Tables;
use IAWPSCOPED\Illuminate\Database\Query\Builder;
use IAWPSCOPED\Illuminate\Database\Query\JoinClause;
/** @internal */
class Campaigns extends \IAWP\Rows\Rows
{
    public function attach_filters(Builder $query) : void
    {
        $query->joinSub($this->query(\true), 'campaign_rows', function (JoinClause $join) {
            $join->on('campaign_rows.campaign_id', '=', 'sessions.campaign_id');
        });
    }
    protected function fetch_rows() : array
    {
        $rows = $this->query()->get()->all();
        return \array_map(function ($row) {
            return new Campaign($row);
        }, $rows);
    }
    private function query(?bool $skip_pagination = \false) : Builder
    {
        if ($skip_pagination) {
            $this->number_of_rows = null;
        }
        $views_table = Query::get_table_name(Query::VIEWS);
        $sessions_table = Query::get_table_name(Query::SESSIONS);
        $orders_table = Query::get_table_name(Query::ORDERS);
        $campaigns_table = Query::get_table_name(Query::CAMPAIGNS);
        $session_statistics = Illuminate_Builder::new();
        $session_statistics->select('sessions.*')->selectRaw('COUNT(DISTINCT views.id) AS views')->selectRaw('COUNT(DISTINCT clicks.click_id) AS clicks')->selectRaw('COUNT(DISTINCT orders.order_id) AS wc_orders')->selectRaw('IFNULL(CAST(SUM(orders.total) AS SIGNED), 0) AS wc_gross_sales')->selectRaw('IFNULL(CAST(SUM(orders.total_refunded) AS SIGNED), 0) AS wc_refunded_amount')->selectRaw('IFNULL(CAST(SUM(orders.total_refunds) AS SIGNED), 0) AS wc_refunds')->selectRaw('IFNULL(SUM(form_submissions.form_submissions), 0) AS form_submissions')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("SUM(IF(form_submissions.form_id = ?, form_submissions.form_submissions, 0)) AS {$form->submissions_column()}", [$form->id()]);
            }
        })->from("{$sessions_table} AS sessions")->join("{$views_table} AS views", function (JoinClause $join) {
            $join->on('sessions.session_id', '=', 'views.session_id');
        })->leftJoin("{$orders_table} AS orders", function (JoinClause $join) {
            $join->on('views.id', '=', 'orders.initial_view_id')->where('orders.is_included_in_analytics', '=', \true);
        })->leftJoin("{$this->tables::clicks()} AS clicks", function (JoinClause $join) {
            $join->on('views.id', '=', 'clicks.view_id');
        })->tap(Query_Taps::tap_authored_content_check())->when(!$this->appears_to_be_for_real_time_analytics(), function (Builder $query) {
            $query->whereBetween('sessions.created_at', $this->get_current_period_iso_range());
        })->whereBetween('views.viewed_at', $this->get_current_period_iso_range())->leftJoinSub($this->get_form_submissions_query(), 'form_submissions', function (JoinClause $join) {
            $join->on('form_submissions.view_id', '=', 'views.id');
        })->whereNotNull('sessions.campaign_id')->groupBy('sessions.session_id');
        $campaigns_query = Illuminate_Builder::new();
        $campaigns_query->select('campaigns.campaign_id', 'landing_page_title AS title', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content')->selectRaw('IFNULL(CAST(SUM(sessions.views) AS SIGNED), 0) AS views')->selectRaw('COUNT(DISTINCT sessions.visitor_id)  AS visitors')->selectRaw('COUNT(DISTINCT sessions.session_id)  AS sessions')->selectRaw('ROUND(AVG( TIMESTAMPDIFF(SECOND, sessions.created_at, sessions.ended_at))) AS average_session_duration')->selectRaw('COUNT(DISTINCT IF(sessions.final_view_id IS NULL, sessions.session_id, NULL))  AS bounces')->selectRaw('SUM(sessions.clicks)  AS clicks')->selectRaw('SUM(sessions.wc_orders) AS wc_orders')->selectRaw('SUM(sessions.wc_gross_sales) AS wc_gross_sales')->selectRaw('SUM(sessions.wc_refunded_amount) AS wc_refunded_amount')->selectRaw('SUM(sessions.wc_refunds) AS wc_refunds')->selectRaw('SUM(sessions.form_submissions) AS form_submissions')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("SUM(sessions.{$form->submissions_column()}) AS {$form->submissions_column()}");
            }
        })->fromSub($session_statistics, 'sessions')->join($campaigns_query->raw($campaigns_table . ' AS campaigns'), function (JoinClause $join) {
            $join->on('sessions.campaign_id', '=', 'campaigns.campaign_id');
        })->when(!$this->appears_to_be_for_real_time_analytics(), function (Builder $query) {
            $query->whereBetween('sessions.created_at', $this->get_current_period_iso_range());
        })->when(\count($this->filters) > 0, function (Builder $query) {
            foreach ($this->filters as $filter) {
                if (!$this->is_a_calculated_column($filter->column())) {
                    $filter->apply_to_query($query);
                }
            }
        })->groupBy('campaigns.campaign_id')->having('views', '>', 0)->when(!$this->is_using_a_calculated_column(), function (Builder $query) {
            $query->when($this->sort_configuration->is_column_nullable(), function (Builder $query) {
                $query->orderByRaw("CASE WHEN {$this->sort_configuration->column()} IS NULL THEN 1 ELSE 0 END");
            })->orderBy($this->sort_configuration->column(), $this->sort_configuration->direction())->orderBy('title')->when(\is_int($this->number_of_rows), function (Builder $query) {
                $query->limit($this->number_of_rows);
            });
        });
        $previous_period_query = Illuminate_Builder::new();
        $previous_period_query->select(['sessions.campaign_id'])->selectRaw('SUM(sessions.total_views) AS previous_period_views')->selectRaw('COUNT(DISTINCT sessions.visitor_id) AS previous_period_visitors')->from($sessions_table, 'sessions')->whereBetween('sessions.created_at', $this->get_previous_period_iso_range())->groupBy('sessions.campaign_id');
        $outer_query = Illuminate_Builder::new();
        $outer_query->selectRaw('campaigns.*')->selectRaw('IF(sessions = 0, 0, views / sessions) AS views_per_session')->selectRaw('IFNULL((views - previous_period_views) / previous_period_views * 100, 0) AS views_growth')->selectRaw('IFNULL((visitors - previous_period_visitors) / previous_period_visitors * 100, 0) AS visitors_growth')->selectRaw('IFNULL(bounces / sessions * 100, 0) AS bounce_rate')->selectRaw('ROUND(CAST(wc_gross_sales - wc_refunded_amount AS SIGNED)) AS wc_net_sales')->selectRaw('IF(visitors = 0, 0, (wc_orders / visitors) * 100) AS wc_conversion_rate')->selectRaw('IF(visitors = 0, 0, (wc_gross_sales - wc_refunded_amount) / visitors) AS wc_earnings_per_visitor')->selectRaw('IF(wc_orders = 0, 0, ROUND(CAST(wc_gross_sales / wc_orders AS SIGNED))) AS wc_average_order_volume')->selectRaw('IF(visitors = 0, 0, (form_submissions / visitors) * 100) AS form_conversion_rate')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("IF(visitors = 0, 0, ({$form->submissions_column()} / visitors) * 100) AS {$form->conversion_rate_column()}");
            }
        })->when(\count($this->filters) > 0, function (Builder $query) {
            foreach ($this->filters as $filter) {
                if ($this->is_a_calculated_column($filter->column())) {
                    $filter->apply_to_query($query);
                }
            }
        })->fromSub($campaigns_query, 'campaigns')->leftJoinSub($previous_period_query, 'previous_period_stats', 'campaigns.campaign_id', '=', 'previous_period_stats.campaign_id')->when($this->is_using_a_calculated_column(), function (Builder $query) {
            $query->when($this->sort_configuration->is_column_nullable(), function (Builder $query) {
                $query->orderByRaw("CASE WHEN {$this->sort_configuration->column()} IS NULL THEN 1 ELSE 0 END");
            })->orderBy($this->sort_configuration->column(), $this->sort_configuration->direction())->orderBy('title')->when(\is_int($this->number_of_rows), function (Builder $query) {
                $query->limit($this->number_of_rows);
            });
        });
        return $outer_query;
    }
}
