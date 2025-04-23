<?php

namespace IAWP\Tables;

use IAWP\Rows\Campaigns;
use IAWP\Statistics\Campaign_Statistics;
use IAWP\Tables\Columns\Column;
use IAWP\Tables\Groups\Group;
use IAWP\Tables\Groups\Groups;
/** @internal */
class Table_Campaigns extends \IAWP\Tables\Table
{
    protected function table_name() : string
    {
        return 'campaigns';
    }
    protected function groups() : Groups
    {
        $groups = [];
        $groups[] = new Group('campaign', \__('Campaign', 'independent-analytics'), 'title', Campaigns::class, Campaign_Statistics::class);
        return new Groups($groups);
    }
    protected function local_columns() : array
    {
        $columns = [new Column(['id' => 'title', 'name' => \__('Landing Page', 'independent-analytics'), 'visible' => \true, 'type' => 'string']), new Column(['id' => 'utm_source', 'name' => \__('Source', 'independent-analytics'), 'visible' => \true, 'type' => 'string']), new Column(['id' => 'utm_medium', 'name' => \__('Medium', 'independent-analytics'), 'visible' => \true, 'type' => 'string']), new Column(['id' => 'utm_campaign', 'name' => \__('Campaign', 'independent-analytics'), 'visible' => \true, 'type' => 'string']), new Column(['id' => 'utm_term', 'name' => \__('Term', 'independent-analytics'), 'type' => 'string', 'is_nullable' => \true]), new Column(['id' => 'utm_content', 'name' => \__('Content', 'independent-analytics'), 'type' => 'string', 'is_nullable' => \true]), new Column(['id' => 'visitors', 'name' => \__('Visitors', 'independent-analytics'), 'visible' => \true, 'type' => 'int', 'aggregatable' => \true]), new Column(['id' => 'views', 'name' => \__('Views', 'independent-analytics'), 'visible' => \true, 'type' => 'int', 'aggregatable' => \true]), new Column(['id' => 'sessions', 'name' => \__('Sessions', 'independent-analytics'), 'type' => 'int', 'aggregatable' => \true]), new Column(['id' => 'average_session_duration', 'name' => \__('Session Duration', 'independent-analytics'), 'visible' => \true, 'type' => 'int', 'filter_placeholder' => 'Seconds']), new Column(['id' => 'views_per_session', 'name' => \__('Views Per Session', 'independent-analytics'), 'type' => 'int']), new Column(['id' => 'bounce_rate', 'name' => \__('Bounce Rate', 'independent-analytics'), 'visible' => \true, 'type' => 'int']), new Column(['id' => 'visitors_growth', 'name' => \__('Visitors Growth', 'independent-analytics'), 'type' => 'int', 'exportable' => \false]), new Column(['id' => 'views_growth', 'name' => \__('Views Growth', 'independent-analytics'), 'type' => 'int', 'exportable' => \false]), new Column(['id' => 'clicks', 'name' => \__('Clicks', 'independent-analytics'), 'type' => 'int', 'requires_pro' => \true, 'aggregatable' => \true])];
        return \array_merge($columns, $this->get_woocommerce_columns(), $this->get_form_columns());
    }
}
