<?php

namespace IAWP\Migrations;

use IAWP\Report_Finder;
/** @internal */
class Migration_17 extends \IAWP\Migrations\Migration
{
    /**
     * @inheritdoc
     */
    protected $database_version = '17';
    /**
     * @inheritDoc
     */
    protected function migrate() : void
    {
        $this->add_initial_saved_reports();
    }
    private function add_initial_saved_reports() : void
    {
        Report_Finder::new()->insert_report(['name' => \esc_html__('Blog Posts', 'independent-analytics'), 'type' => 'views', 'user_created_report' => 0, 'filters' => [['inclusion' => 'include', 'column' => 'type', 'operator' => 'is', 'operand' => 'post']]]);
        Report_Finder::new()->insert_report(['name' => \esc_html__('Top Landing Pages', 'independent-analytics'), 'type' => 'views', 'user_created_report' => 0, 'sort_column' => 'entrances', 'sort_direction' => 'desc', 'columns' => ['title', 'visitors', 'views', 'average_view_duration', 'bounce_rate', 'entrances', 'url', 'type']]);
        Report_Finder::new()->insert_report(['name' => \esc_html__('Fastest-Growing Pages', 'independent-analytics'), 'type' => 'views', 'user_created_report' => 0, 'sort_column' => 'visitors_growth', 'sort_direction' => 'desc', 'columns' => ['title', 'visitors', 'views', 'average_view_duration', 'bounce_rate', 'visitors_growth', 'url', 'type'], 'filters' => [['inclusion' => 'exclude', 'column' => 'visitors', 'operator' => 'lesser', 'operand' => '5']]]);
        Report_Finder::new()->insert_report(['name' => \esc_html__('Today', 'independent-analytics'), 'type' => 'views', 'user_created_report' => 0, 'relative_range_id' => 'TODAY']);
        Report_Finder::new()->insert_report(['name' => \esc_html__('Search Engine Traffic', 'independent-analytics'), 'type' => 'referrers', 'user_created_report' => 0, 'filters' => [['inclusion' => 'include', 'column' => 'referrer_type', 'operator' => 'is', 'operand' => 'Search']]]);
        Report_Finder::new()->insert_report(['name' => \esc_html__('Social Media Traffic', 'independent-analytics'), 'type' => 'referrers', 'user_created_report' => 0, 'filters' => [['inclusion' => 'include', 'column' => 'referrer_type', 'operator' => 'is', 'operand' => 'Social']]]);
        Report_Finder::new()->insert_report(['name' => \esc_html__('Fastest-Growing Referrers', 'independent-analytics'), 'type' => 'referrers', 'user_created_report' => 0, 'sort_column' => 'visitors_growth', 'sort_direction' => 'desc', 'columns' => ['referrer', 'referrer_type', 'visitors', 'views', 'average_session_duration', 'bounce_rate', 'visitors_growth'], 'filters' => [['inclusion' => 'exclude', 'column' => 'visitors', 'operator' => 'lesser', 'operand' => '5']]]);
        Report_Finder::new()->insert_report(['name' => \esc_html__('Last 7 Days', 'independent-analytics'), 'type' => 'referrers', 'user_created_report' => 0, 'relative_range_id' => 'LAST_SEVEN']);
        Report_Finder::new()->insert_report(['name' => \esc_html__('Cities', 'independent-analytics'), 'type' => 'geo', 'group_name' => 'city', 'user_created_report' => 0]);
        Report_Finder::new()->insert_report(['name' => \esc_html__('European Countries', 'independent-analytics'), 'type' => 'geo', 'user_created_report' => 0, 'filters' => [['inclusion' => 'include', 'column' => 'continent', 'operator' => 'exact', 'operand' => 'Europe']]]);
        Report_Finder::new()->insert_report(['name' => \esc_html__('Browsers', 'independent-analytics'), 'type' => 'devices', 'group_name' => 'browser', 'user_created_report' => 0]);
        Report_Finder::new()->insert_report(['name' => \esc_html_x('OS', 'short for operating system', 'independent-analytics'), 'type' => 'devices', 'group_name' => 'os', 'user_created_report' => 0]);
    }
}
