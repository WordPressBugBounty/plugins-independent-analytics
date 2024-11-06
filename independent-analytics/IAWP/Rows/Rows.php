<?php

namespace IAWP\Rows;

use IAWP\Date_Range\Date_Range;
use IAWP\Env;
use IAWP\Form_Submissions\Form;
use IAWP\Illuminate_Builder;
use IAWP\Query;
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
    private $rows = null;
    public function __construct(Date_Range $date_range, ?int $number_of_rows = null, ?array $filters = null, ?Sort_Configuration $sort_configuration = null)
    {
        $this->date_range = $date_range;
        $this->number_of_rows = $number_of_rows;
        $this->filters = $filters ?? [];
        $this->sort_configuration = $sort_configuration ?? new Sort_Configuration('visitors');
    }
    protected abstract function fetch_rows() : array;
    public abstract function attach_filters(Builder $query) : void;
    public function rows()
    {
        if (\is_array($this->rows)) {
            return $this->rows;
        }
        $this->rows = $this->fetch_rows();
        return $this->rows;
    }
    /**
     * @return string[]
     */
    protected function calculated_columns() : array
    {
        $calculated_columns = ['comments', 'exit_percent', 'views_per_session', 'views_growth', 'visitors_growth', 'bounce_rate', 'wc_net_sales', 'wc_conversion_rate', 'wc_earnings_per_visitor', 'wc_average_order_volume', 'form_conversion_rate'];
        foreach (Form::get_forms() as $form) {
            $calculated_columns[] = $form->conversion_rate_column();
        }
        return $calculated_columns;
    }
    protected function is_a_calculated_column(string $column_name) : bool
    {
        return \in_array($column_name, $this->calculated_columns());
    }
    protected function is_using_a_calculated_column() : bool
    {
        $is_using_a_calculated_column = \false;
        foreach ($this->filters as $filter) {
            if (\in_array($filter->column(), $this->calculated_columns())) {
                $is_using_a_calculated_column = \true;
            }
        }
        if (\in_array($this->sort_configuration->column(), $this->calculated_columns())) {
            $is_using_a_calculated_column = \true;
        }
        return $is_using_a_calculated_column;
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
    protected function get_form_submissions_query() : Builder
    {
        $form_submissions_table = Query::get_table_name(Query::FORM_SUBMISSIONS);
        return Illuminate_Builder::new()->select(['form_id', 'view_id'])->selectRaw('COUNT(*) AS form_submissions')->from($form_submissions_table, 'form_submissions')->whereBetween('created_at', $this->get_current_period_iso_range())->groupBy(['form_id', 'view_id']);
    }
}
