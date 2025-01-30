<?php

namespace IAWP\Rows;

use IAWP\Illuminate_Builder;
use IAWP\Models\Click;
use IAWP\Query_Taps;
use IAWPSCOPED\Illuminate\Database\Query\Builder;
use IAWPSCOPED\Illuminate\Database\Query\JoinClause;
/** @internal */
class Links extends \IAWP\Rows\Rows
{
    public function attach_filters(Builder $query) : void
    {
        $query->joinSub($this->query(\true), 'click_rows', function (JoinClause $join) {
            $join->on('click_rows.link_rule_id', '=', 'link_rules.link_rule_id');
            $join->on('click_rows.click_target_id', '=', 'click_targets.click_target_id');
        });
    }
    protected function fetch_rows() : array
    {
        $rows = $this->query()->get()->all();
        return \array_map(function ($row) {
            return new Click($row);
        }, $rows);
    }
    private function query(?bool $skip_pagination = \false) : Builder
    {
        if ($skip_pagination) {
            $this->number_of_rows = null;
        }
        $query = Illuminate_Builder::new()->select(['link_rules.link_rule_id AS link_rule_id', 'click_targets.click_target_id AS click_target_id', 'link_rules.name AS link_name', 'click_targets.target AS link_target'])->selectRaw('COUNT(DISTINCT clicks.click_id) AS link_clicks')->from($this->tables::link_rules(), 'link_rules')->leftJoin($this->tables::clicked_links() . ' AS clicked_links', 'clicked_links.link_rule_id', '=', 'link_rules.link_rule_id')->leftJoin($this->tables::clicks() . ' AS clicks', 'clicks.click_id', '=', 'clicked_links.click_id')->leftJoin($this->tables::click_targets() . ' AS click_targets', 'click_targets.click_target_id', '=', 'clicks.click_target_id')->whereBetween('clicks.created_at', $this->get_current_period_iso_range())->when(\count($this->filters) > 0, function (Builder $query) {
            foreach ($this->filters as $filter) {
                if (!$this->is_a_calculated_column($filter->column())) {
                    $filter->apply_to_query($query);
                }
            }
        })->when(\is_int($this->number_of_rows), function (Builder $query) {
            $query->limit($this->number_of_rows);
        })->tap(Query_Taps::tap_authored_content_for_clicks())->orderBy($this->sort_configuration->column(), $this->sort_configuration->direction())->orderBy('link_name')->groupBy('link_rules.link_rule_id', 'clicks.click_target_id');
        return $query;
    }
}
