<?php

namespace IAWP\Tables;

use IAWP\Filter_Lists\Author_Filter_List;
use IAWP\Filter_Lists\Category_Filter_List;
use IAWP\Filter_Lists\Page_Type_Filter_List;
use IAWP\Rows\Pages;
use IAWP\Statistics\Page_Statistics;
use IAWP\Tables\Columns\Column;
use IAWP\Tables\Groups\Group;
use IAWP\Tables\Groups\Groups;
/** @internal */
class Table_Pages extends \IAWP\Tables\Table
{
    protected function table_name() : string
    {
        return 'views';
    }
    protected function groups() : Groups
    {
        $groups = [];
        $groups[] = new Group('page', \__('Page', 'independent-analytics'), Pages::class, Page_Statistics::class);
        return new Groups($groups);
    }
    protected function local_columns() : array
    {
        $columns = [new Column(['id' => 'title', 'name' => \__('Title', 'independent-analytics'), 'visible' => \true, 'type' => 'string', 'database_column' => 'cached_title']), new Column(['id' => 'visitors', 'name' => \__('Visitors', 'independent-analytics'), 'visible' => \true, 'type' => 'int']), new Column(['id' => 'views', 'name' => \__('Views', 'independent-analytics'), 'visible' => \true, 'type' => 'int']), new Column(['id' => 'sessions', 'name' => \__('Sessions', 'independent-analytics'), 'type' => 'int']), new Column(['id' => 'average_view_duration', 'name' => \__('View Duration', 'independent-analytics'), 'visible' => \true, 'type' => 'int', 'filter_placeholder' => 'Seconds']), new Column(['id' => 'bounce_rate', 'name' => \__('Bounce Rate', 'independent-analytics'), 'visible' => \true, 'type' => 'int']), new Column(['id' => 'visitors_growth', 'name' => \__('Visitors Growth', 'independent-analytics'), 'type' => 'int', 'exportable' => \false]), new Column(['id' => 'views_growth', 'name' => \__('Views Growth', 'independent-analytics'), 'type' => 'int', 'exportable' => \false]), new Column(['id' => 'entrances', 'name' => \__('Entrances', 'independent-analytics'), 'type' => 'int']), new Column(['id' => 'exits', 'name' => \__('Exits', 'independent-analytics'), 'type' => 'int']), new Column(['id' => 'exit_percent', 'name' => \__('Exit Rate', 'independent-analytics'), 'type' => 'int']), new Column(['id' => 'clicks', 'name' => \__('Clicks', 'independent-analytics'), 'type' => 'int', 'requires_pro' => \true]), new Column(['id' => 'url', 'name' => \__('URL', 'independent-analytics'), 'visible' => \true, 'type' => 'string', 'database_column' => 'cached_url']), new Column(['id' => 'author', 'name' => \__('Author', 'independent-analytics'), 'type' => 'select', 'options' => Author_Filter_List::options(), 'database_column' => 'cached_author_id', 'is_nullable' => \true]), new Column(['id' => 'type', 'name' => \__('Page Type', 'independent-analytics'), 'visible' => \true, 'type' => 'select', 'options' => Page_Type_Filter_List::options(), 'database_column' => 'cached_type', 'is_nullable' => \true]), new Column(['id' => 'date', 'name' => \__('Publish Date', 'independent-analytics'), 'type' => 'date', 'database_column' => 'cached_date', 'is_nullable' => \true]), new Column(['id' => 'category', 'name' => \__('Post Category', 'independent-analytics'), 'type' => 'select', 'options' => Category_Filter_List::options(), 'database_column' => 'cached_category', 'is_nullable' => \true]), new Column(['id' => 'comments', 'name' => \__('Comments', 'independent-analytics'), 'type' => 'int', 'is_nullable' => \true])];
        return \array_merge($columns, $this->get_woocommerce_columns(), $this->get_form_columns());
    }
}
