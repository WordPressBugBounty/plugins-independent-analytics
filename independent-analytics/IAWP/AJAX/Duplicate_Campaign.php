<?php

namespace IAWP\AJAX;

use IAWP\Campaign_Builder;
/** @internal */
class Duplicate_Campaign extends \IAWP\AJAX\AJAX
{
    protected function action_required_fields() : array
    {
        return ['campaign_url_id'];
    }
    protected function action_name() : string
    {
        return 'iawp_duplicate_campaign';
    }
    protected function requires_pro() : bool
    {
        return \true;
    }
    protected function action_callback() : void
    {
        $campaign_url_id = $this->get_int_field('campaign_url_id');
        if (\is_null($campaign_url_id) || $campaign_url_id < 1) {
            \wp_send_json_error(['error' => 'invalid_campaign_url_id'], 400);
            return;
        }
        $new_campaign_url_id = Campaign_Builder::duplicate_campaign($campaign_url_id);
        if (\is_null($new_campaign_url_id)) {
            \wp_send_json_error(['error' => 'campaign_url_not_found'], 404);
            return;
        }
        \wp_send_json_success(['campaign_url_id' => $new_campaign_url_id]);
    }
}
