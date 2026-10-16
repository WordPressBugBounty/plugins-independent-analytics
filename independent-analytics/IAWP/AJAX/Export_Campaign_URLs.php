<?php

namespace IAWP\AJAX;

use IAWP\Campaign_Builder;
use InvalidArgumentException;
/** @internal */
class Export_Campaign_URLs extends \IAWP\AJAX\AJAX
{
    protected function action_name() : string
    {
        return 'iawp_export_campaign_urls';
    }
    protected function requires_pro() : bool
    {
        return \true;
    }
    protected function action_callback() : void
    {
        $filters = $this->get_array_field('filters') ?? [];
        try {
            $csv = (new Campaign_Builder())->campaign_urls_csv($filters)->to_string();
        } catch (InvalidArgumentException) {
            \wp_send_json_error(['error' => 'invalid_request'], 400);
            return;
        }
        \wp_send_json_success(['csv' => $csv]);
    }
}
