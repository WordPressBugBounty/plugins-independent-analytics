<?php

namespace IAWP\AJAX;

use IAWP\Campaign_Autocomplete_Query;
use InvalidArgumentException;
/** @internal */
class Campaign_Autocomplete extends \IAWP\AJAX\AJAX
{
    protected function action_name() : string
    {
        return 'iawp_campaign_autocomplete';
    }
    protected function action_required_fields() : array
    {
        return ['query'];
    }
    protected function requires_pro() : bool
    {
        return \true;
    }
    protected function action_callback() : void
    {
        $query = $this->get_string_field('query');
        $field = $this->get_string_field('field');
        try {
            $options = (new Campaign_Autocomplete_Query())->options($query, $field);
        } catch (InvalidArgumentException) {
            \wp_send_json_error(['error' => 'invalid_field'], 400);
            return;
        }
        \wp_send_json_success($options);
    }
}
