<?php

namespace IAWP\AJAX;

use IAWP\Campaign_Builder;
/** @internal */
class Create_Campaign extends \IAWP\AJAX\AJAX
{
    protected function action_required_fields() : array
    {
        return ['path', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'note'];
    }
    protected function action_name() : string
    {
        return 'iawp_create_campaign';
    }
    protected function requires_pro() : bool
    {
        return \true;
    }
    protected function action_callback() : void
    {
        $campaign_builder = new Campaign_Builder();
        $result = $campaign_builder->create_campaign($this->get_string_field('path'), $this->get_string_field('utm_source'), $this->get_string_field('utm_medium'), $this->get_string_field('utm_campaign'), $this->get_string_field('utm_term'), $this->get_string_field('utm_content'), $this->get_string_field('note'));
        if (\count($result['errors']) > 0) {
            \wp_send_json_error(['errors' => $result['errors']], 400);
            return;
        }
        \wp_send_json_success(['campaign_url_id' => $result['campaign_url_id'], 'url' => $result['url']]);
    }
}
