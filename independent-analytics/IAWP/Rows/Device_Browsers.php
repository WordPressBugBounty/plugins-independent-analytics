<?php

namespace IAWP\Rows;

use IAWP\Form_Submissions\Form;
use IAWP\Illuminate_Builder;
use IAWP\Models\Device;
use IAWP\Query;
use IAWP\Query_Taps;
use IAWP\Tables;
use IAWPSCOPED\Illuminate\Database\Query\Builder;
use IAWPSCOPED\Illuminate\Database\Query\JoinClause;
/** @internal */
class Device_Browsers extends \IAWP\Rows\Rows
{
    public function attach_filters(Builder $query) : void
    {
        $query->joinSub($this->query(\true), 'device_browser_rows', function (JoinClause $join) {
            $join->on('device_browser_rows.device_browser_id', '=', 'sessions.device_browser_id');
        });
    }
    protected function fetch_rows() : array
    {
        $rows = $this->query()->get()->all();
        return \array_map(function ($row) {
            return new Device($row);
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
        $device_browsers_table = Query::get_table_name(Query::DEVICE_BROWSERS);
        $orders_query = Illuminate_Builder::new();
        $orders_query->select(['orders.view_id AS view_id'])->selectRaw('IFNULL(COUNT(DISTINCT orders.order_id), 0) AS wc_orders')->selectRaw('IFNULL(ROUND(CAST(SUM(orders.total) AS SIGNED)), 0) AS wc_gross_sales')->selectRaw('IFNULL(ROUND(CAST(SUM(orders.total_refunded) AS SIGNED)), 0) AS wc_refunded_amount')->selectRaw('IFNULL(SUM(orders.total_refunds), 0) AS wc_refunds')->from($orders_table, 'orders')->where('orders.is_included_in_analytics', '=', \true)->whereBetween('orders.created_at', $this->get_current_period_iso_range())->groupBy('orders.view_id');
        $device_browsers_query = Illuminate_Builder::new();
        $device_browsers_query->select('device_browsers.device_browser_id', 'device_browsers.device_browser AS browser')->selectRaw('COUNT(DISTINCT views.id)  AS views')->selectRaw('COUNT(DISTINCT sessions.visitor_id)  AS visitors')->selectRaw('COUNT(DISTINCT sessions.session_id)  AS sessions')->selectRaw('ROUND(AVG( TIMESTAMPDIFF(SECOND, sessions.created_at, sessions.ended_at))) AS average_session_duration')->selectRaw('COUNT(DISTINCT IF(sessions.final_view_id IS NULL, sessions.session_id, NULL))  AS bounces')->selectRaw('COUNT(DISTINCT clicks.click_id)  AS clicks')->selectRaw('IFNULL(SUM(the_orders.wc_orders), 0) AS wc_orders')->selectRaw('IFNULL(SUM(the_orders.wc_gross_sales), 0) AS wc_gross_sales')->selectRaw('IFNULL(SUM(the_orders.wc_refunded_amount), 0) AS wc_refunded_amount')->selectRaw('IFNULL(SUM(the_orders.wc_refunds), 0) AS wc_refunds')->selectRaw('IFNULL(SUM(form_submissions.form_submissions), 0) AS form_submissions')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("SUM(IF(form_submissions.form_id = ?, form_submissions.form_submissions, 0)) AS {$form->submissions_column()}", [$form->id()]);
            }
        })->from($views_table, 'views')->leftJoin($device_browsers_query->raw($sessions_table . ' AS sessions'), function (JoinClause $join) {
            $join->on('views.session_id', '=', 'sessions.session_id');
        })->join($device_browsers_query->raw($device_browsers_table . ' AS device_browsers'), function (JoinClause $join) {
            $join->on('sessions.device_browser_id', '=', 'device_browsers.device_browser_id');
        })->leftJoin($device_browsers_query->raw(Tables::clicks() . ' AS clicks'), function (JoinClause $join) {
            $join->on('clicks.view_id', '=', 'views.id');
        })->leftJoinSub($orders_query, 'the_orders', function (JoinClause $join) {
            $join->on('the_orders.view_id', '=', 'views.id');
        })->leftJoinSub($this->get_form_submissions_query(), 'form_submissions', function (JoinClause $join) {
            $join->on('form_submissions.view_id', '=', 'views.id');
        })->whereBetween('views.viewed_at', $this->get_current_period_iso_range())->when(!$this->appears_to_be_for_real_time_analytics(), function (Builder $query) {
            $query->whereBetween('sessions.created_at', $this->get_current_period_iso_range());
        })->tap(Query_Taps::tap_authored_content_check())->when(\count($this->filters) > 0, function (Builder $query) {
            foreach ($this->filters as $filter) {
                if (!$this->is_a_calculated_column($filter->column())) {
                    $filter->apply_to_query($query);
                }
            }
        })->groupBy('device_browsers.device_browser_id')->having('views', '>', 0)->when(!$this->is_using_a_calculated_column(), function (Builder $query) {
            $query->when($this->sort_configuration->is_column_nullable(), function (Builder $query) {
                $query->orderByRaw("CASE WHEN {$this->sort_configuration->column()} IS NULL THEN 1 ELSE 0 END");
            })->orderBy($this->sort_configuration->column(), $this->sort_configuration->direction())->orderBy('browser')->when(\is_int($this->number_of_rows), function (Builder $query) {
                $query->limit($this->number_of_rows);
            });
        });
        $previous_period_query = Illuminate_Builder::new();
        $previous_period_query->select(['sessions.device_browser_id'])->selectRaw('SUM(sessions.total_views) AS previous_period_views')->selectRaw('COUNT(DISTINCT sessions.visitor_id) AS previous_period_visitors')->from($sessions_table, 'sessions')->whereBetween('sessions.created_at', $this->get_previous_period_iso_range())->groupBy('sessions.device_browser_id');
        $outer_query = Illuminate_Builder::new();
        $outer_query->selectRaw('device_browsers.*')->selectRaw('IF(sessions = 0, 0, views / sessions) AS views_per_session')->selectRaw('IFNULL((views - previous_period_views) / previous_period_views * 100, 0) AS views_growth')->selectRaw('IFNULL((visitors - previous_period_visitors) / previous_period_visitors * 100, 0) AS visitors_growth')->selectRaw('IFNULL(bounces / sessions * 100, 0) AS bounce_rate')->selectRaw('ROUND(CAST(wc_gross_sales - wc_refunded_amount AS SIGNED)) AS wc_net_sales')->selectRaw('IF(visitors = 0, 0, (wc_orders / visitors) * 100) AS wc_conversion_rate')->selectRaw('IF(visitors = 0, 0, (wc_gross_sales - wc_refunded_amount) / visitors) AS wc_earnings_per_visitor')->selectRaw('IF(wc_orders = 0, 0, ROUND(CAST(wc_gross_sales / wc_orders AS SIGNED))) AS wc_average_order_volume')->selectRaw('IF(visitors = 0, 0, (form_submissions / visitors) * 100) AS form_conversion_rate')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("IF(visitors = 0, 0, ({$form->submissions_column()} / visitors) * 100) AS {$form->conversion_rate_column()}");
            }
        })->when(\count($this->filters) > 0, function (Builder $query) {
            foreach ($this->filters as $filter) {
                if ($this->is_a_calculated_column($filter->column())) {
                    $filter->apply_to_query($query);
                }
            }
        })->fromSub($device_browsers_query, 'device_browsers')->leftJoinSub($previous_period_query, 'previous_period_stats', 'device_browsers.device_browser_id', '=', 'previous_period_stats.device_browser_id')->when($this->is_using_a_calculated_column(), function (Builder $query) {
            $query->when($this->sort_configuration->is_column_nullable(), function (Builder $query) {
                $query->orderByRaw("CASE WHEN {$this->sort_configuration->column()} IS NULL THEN 1 ELSE 0 END");
            })->orderBy($this->sort_configuration->column(), $this->sort_configuration->direction())->orderBy('browser')->when(\is_int($this->number_of_rows), function (Builder $query) {
                $query->limit($this->number_of_rows);
            });
        });
        return $outer_query;
    }
}
