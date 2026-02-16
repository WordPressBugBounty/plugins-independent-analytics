<?php

namespace IAWP\Rows;

use IAWP\Date_Range\Date_Range;
use IAWP\Examiner_Config;
use IAWP\Form_Submissions\Form;
use IAWP\Illuminate_Builder;
use IAWP\Query_Taps;
use IAWP\Sort_Configuration;
use IAWP\Tables;
use IAWPSCOPED\Illuminate\Database\Query\Builder;
/** @internal */
abstract class Rows
{
    protected $tables = Tables::class;
    protected $date_range;
    protected $number_of_rows;
    /** @var Filter[] */
    protected $filters;
    protected $sort_configuration;
    protected $filter_logic;
    protected $solo_record_id = null;
    protected $examiner_config = null;
    private $rows = null;
    private $cached_filter_query = null;
    public function __construct(Date_Range $date_range, Sort_Configuration $sort_configuration, ?int $number_of_rows = null, ?array $filters = null, ?string $filter_logic = null)
    {
        $this->date_range = $date_range;
        $this->sort_configuration = $sort_configuration;
        $this->number_of_rows = $number_of_rows;
        $this->filters = $filters ?? [];
        $this->filter_logic = \in_array($filter_logic, ['and', 'or']) ? $filter_logic : 'and';
    }
    protected abstract function fetch_rows() : array;
    public abstract function attach_filters(Builder $query) : void;
    protected abstract function query(?bool $skip_pagination = \false) : Builder;
    protected abstract function sort_tie_breaker_column() : string;
    /**
     * Used to limit the rows to just a single record. This is useful if you want a single page,
     * referrer, etc. row, and you know the records database id.
     *
     * @param int $id
     *
     * @return void
     */
    public function limit_to(int $id) : void
    {
        $this->solo_record_id = $id;
    }
    public function for_examiner(Examiner_Config $config)
    {
        $this->examiner_config = $config;
    }
    public function rows()
    {
        if (\is_array($this->rows)) {
            return $this->rows;
        }
        $this->rows = $this->fetch_rows();
        return $this->rows;
    }
    protected function get_filter_query() : Builder
    {
        if ($this->cached_filter_query === null) {
            $this->cached_filter_query = $this->query(\true);
        }
        return clone $this->cached_filter_query;
    }
    protected function only_filtering_by_record_columns() : bool
    {
        if (\count($this->filters) === 0) {
            return \true;
        }
        foreach ($this->filters as $filter) {
            if ($filter->is_calculated_column()) {
                return \false;
            }
        }
        return \true;
    }
    protected function only_filtering_by_aggregate_columns() : bool
    {
        if (\count($this->filters) === 0) {
            return \false;
        }
        foreach ($this->filters as $filter) {
            if ($filter->is_concrete_column()) {
                return \false;
            }
        }
        return \true;
    }
    protected function filtering_by_mixed_columns() : bool
    {
        if (\count($this->filters) === 0) {
            return \false;
        }
        $has_record_column = \false;
        $has_aggregate_column = \false;
        foreach ($this->filters as $filter) {
            if ($filter->is_calculated_column()) {
                $has_aggregate_column = \true;
            } else {
                $has_record_column = \true;
            }
            if ($has_record_column && $has_aggregate_column) {
                return \true;
            }
        }
        return \false;
    }
    protected function get_current_period_iso_range() : array
    {
        return [$this->date_range->iso_start(), $this->date_range->iso_end()];
    }
    protected function appears_to_be_for_real_time_analytics() : bool
    {
        $difference_in_seconds = $this->date_range->end()->getTimestamp() - $this->date_range->start()->getTimestamp();
        $one_hour_in_seconds = 3600;
        return $difference_in_seconds < $one_hour_in_seconds;
    }
    protected function get_previous_period_iso_range() : array
    {
        return [$this->date_range->previous_period()->iso_start(), $this->date_range->previous_period()->iso_end()];
    }
    protected function apply_record_filters(Builder $query) : void
    {
        $should_apply_record_filters = $this->using_logical_and_operator() || $this->only_filtering_by_record_columns();
        if (!$should_apply_record_filters) {
            return;
        }
        if ($this->using_logical_or_operator()) {
            $query->where(function (Builder $query) {
                foreach ($this->filters as $index => $filter) {
                    $filter->apply_to_query($query, \IAWP\Rows\Filter::$RECORD_FILTER, $index > 0);
                }
            });
            return;
        }
        foreach ($this->filters as $filter) {
            if ($filter->is_concrete_column()) {
                $filter->apply_to_query($query, \IAWP\Rows\Filter::$RECORD_FILTER);
            }
        }
    }
    protected function can_order_and_limit_at_record_level() : bool
    {
        return $this->sort_configuration->the_column()->is_concrete_column() && $this->only_filtering_by_record_columns();
    }
    protected function apply_aggregate_filters(Builder $query) : void
    {
        $should_apply_aggregate_filters = $this->using_logical_and_operator() || $this->using_logical_or_operator() && $this->only_filtering_by_aggregate_columns();
        if (!$should_apply_aggregate_filters) {
            return;
        }
        if ($this->using_logical_or_operator()) {
            $query->where(function (Builder $query) {
                foreach ($this->filters as $index => $filter) {
                    $filter->apply_to_query($query, \IAWP\Rows\Filter::$AGGREGATE_FILTER, $index > 0);
                }
            });
            return;
        }
        foreach ($this->filters as $filter) {
            if ($filter->is_calculated_column()) {
                $filter->apply_to_query($query, \IAWP\Rows\Filter::$AGGREGATE_FILTER);
            }
        }
    }
    protected function apply_or_filters(Builder $query) : void
    {
        $query->where(function (Builder $query) {
            foreach ($this->filters as $index => $filter) {
                $filter->apply_to_query($query, \IAWP\Rows\Filter::$OUTER_FILTER, $index > 0);
            }
        });
    }
    protected function using_logical_or_operator() : bool
    {
        return $this->filter_logic === 'or' && \count($this->filters) > 1;
    }
    protected function using_logical_and_operator() : bool
    {
        return !$this->using_logical_or_operator();
    }
    protected function apply_order_and_limit(Builder $query, string $sort_column) : void
    {
        $query->when($this->sort_configuration->the_column()->is_nullable(), function (Builder $query) use($sort_column) {
            $query->orderByRaw("CASE WHEN {$sort_column} IS NULL THEN 1 ELSE 0 END");
        })->orderBy($sort_column, $this->sort_configuration->direction())->orderBy($this->sort_tie_breaker_column())->when(\is_int($this->number_of_rows), function (Builder $query) {
            $query->limit($this->number_of_rows);
        });
    }
    protected function session_statistics_query() : Builder
    {
        $orders_subquery = Illuminate_Builder::new()->selectRaw('views.session_id')->selectRaw('COUNT(*) as order_count')->selectRaw('IFNULL(SUM(orders.total), 0) as total')->selectRaw('IFNULL(SUM(orders.total_refunded), 0) as total_refunded')->selectRaw('IFNULL(SUM(orders.total_refunds), 0) as total_refunds')->from(Tables::orders() . " AS orders")->join(Tables::views() . " AS views", 'orders.initial_view_id', '=', 'views.id')->where('orders.is_included_in_analytics', '=', \true)->whereBetween('orders.created_at', $this->get_current_period_iso_range())->groupBy('views.session_id');
        $form_submissions_subquery = Illuminate_Builder::new()->select(['views.session_id'])->selectRaw('COUNT(*) as form_submissions')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw('SUM(submissions.form_id = ?) as ' . $form->submissions_column(), [$form->id()]);
            }
        })->from(Tables::form_submissions(), 'submissions')->leftJoin(Tables::views() . ' AS views', 'views.id', '=', 'submissions.view_id')->whereBetween('submissions.created_at', $this->get_current_period_iso_range())->groupBy('views.session_id');
        $views_subquery = Illuminate_Builder::new()->select('views.session_id')->selectRaw('COUNT(DISTINCT views.id) AS views')->selectRaw('COUNT(DISTINCT clicks.click_id) AS clicks')->from(Tables::views(), 'views')->leftJoin(Tables::clicks() . ' AS clicks', 'clicks.view_id', '=', 'views.id')->whereBetween('views.viewed_at', $this->get_current_period_iso_range())->groupBy('views.session_id');
        return Illuminate_Builder::new()->select('sessions.*')->selectRaw('views.views AS views')->selectRaw('views.clicks AS clicks')->selectRaw('IFNULL(order_stats.order_count, 0) AS wc_orders')->selectRaw('IFNULL(order_stats.total, 0) AS wc_gross_sales')->selectRaw('IFNULL(order_stats.total_refunded, 0) AS wc_refunded_amount')->selectRaw('IFNULL(order_stats.total_refunds, 0) AS wc_refunds')->selectRaw('IFNULL(form_submissions.form_submissions, 0) AS form_submissions')->tap(function (Builder $query) {
            foreach (Form::get_forms() as $form) {
                $query->selectRaw("IFNULL(form_submissions.{$form->submissions_column()}, 0) AS {$form->submissions_column()}");
            }
        })->from(Tables::sessions(), 'sessions')->joinSub($views_subquery, 'views', 'sessions.session_id', '=', 'views.session_id')->leftJoinSub($orders_subquery, 'order_stats', 'sessions.session_id', '=', 'order_stats.session_id')->leftJoinSub($form_submissions_subquery, 'form_submissions', 'sessions.session_id', '=', 'form_submissions.session_id')->tap(Query_Taps::tap_authored_content_check())->tap(Query_Taps::tap_related_to_examined_record($this->examiner_config))->when(!$this->appears_to_be_for_real_time_analytics(), function (Builder $query) {
            $query->whereBetween('sessions.created_at', $this->get_current_period_iso_range());
        });
    }
}
