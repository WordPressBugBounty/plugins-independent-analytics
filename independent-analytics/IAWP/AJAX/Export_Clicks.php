<?php

namespace IAWP\AJAX;

use IAWP\Capability_Manager;
use IAWP\Date_Range\Exact_Date_Range;
use IAWP\Rows\Clicks;
use IAWP\Tables\Table_Clicks;
/** @internal */
class Export_Clicks extends \IAWP\AJAX\AJAX
{
    protected function action_name() : string
    {
        return 'iawp_export_clicks';
    }
    protected function requires_pro() : bool
    {
        return \true;
    }
    protected function action_callback() : void
    {
        if (!Capability_Manager::can_edit()) {
            return;
        }
        $table = new Table_Clicks();
        $clicks = new Clicks(Exact_Date_Range::comprehensive_range(), null, null, $table->sanitize_sort_parameters());
        $csv = $table->csv($clicks->rows());
        echo $csv->to_string();
    }
}
