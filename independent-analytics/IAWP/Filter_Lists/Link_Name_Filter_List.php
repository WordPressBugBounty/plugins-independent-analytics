<?php

namespace IAWP\Filter_Lists;

use IAWP\Illuminate_Builder;
use IAWP\Tables;
/** @internal */
class Link_Name_Filter_List
{
    use \IAWP\Filter_Lists\Filter_List_Trait;
    protected static function fetch_options() : array
    {
        $records = Illuminate_Builder::new()->from(Tables::link_rules())->select('link_rule_id', 'name')->get()->all();
        return \array_map(function ($record) {
            return [$record->link_rule_id, $record->name];
        }, $records);
    }
}
