<?php

namespace IAWP\AJAX;

use IAWP\Campaign_Builder;
use InvalidArgumentException;
/** @internal */
class Get_Campaign_List extends \IAWP\AJAX\AJAX
{
    protected function action_name() : string
    {
        return 'iawp_get_campaign_list';
    }
    protected function requires_pro() : bool
    {
        return \true;
    }
    protected function action_callback() : void
    {
        $filters = $this->get_array_field('filters') ?? [];
        try {
            $response = (new Campaign_Builder())->render_campaign_list($filters);
        } catch (InvalidArgumentException) {
            \wp_send_json_error(['error' => 'invalid_request'], 400);
            return;
        }
        \wp_send_json_success($response);
    }
}
