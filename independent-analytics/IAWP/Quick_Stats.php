<?php

namespace IAWP;

use IAWP\Statistics\Statistic;
use IAWP\Statistics\Statistics;
/** @internal */
class Quick_Stats
{
    private $statistics;
    private $is_dashboard_widget;
    /**
     * @param Statistics $statistics
     * @param bool $is_dashboard_widget
     */
    public function __construct(Statistics $statistics, bool $is_dashboard_widget = \false)
    {
        $this->statistics = $statistics;
        $this->is_dashboard_widget = $is_dashboard_widget;
    }
    public function get_html() : string
    {
        $statistics = $this->statistics->get_statistics();
        $visible_quick_stats_count = \count(\array_filter($statistics, function (Statistic $statistic) : bool {
            return $statistic->is_visible() && $statistic->is_group_plugin_enabled();
        }));
        $quick_stats_html_class = "quick-stats total-of-{$visible_quick_stats_count}";
        if ($this->statistics->has_filters()) {
            $quick_stats_html_class .= ' filtered';
        }
        return \IAWPSCOPED\iawp_blade()->run('quick-stats', ['is_dashboard_widget' => $this->is_dashboard_widget, 'quick_stats_html_class' => $quick_stats_html_class, 'statistics' => $statistics, 'plugin_groups' => \IAWP\Plugin_Group::get_plugin_groups()]);
    }
}
