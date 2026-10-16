<?php

namespace IAWP\AJAX;

use IAWP\Campaign_QR_Code_Preferences;
/** @internal */
class Get_Campaign_QR_Code_Preferences extends \IAWP\AJAX\AJAX
{
    protected function action_name() : string
    {
        return 'iawp_get_campaign_qr_code_preferences';
    }
    protected function requires_pro() : bool
    {
        return \true;
    }
    protected function action_callback() : void
    {
        $manager = new Campaign_QR_Code_Preferences();
        $preferences = $manager->get();
        $logo = $manager->logo($preferences);
        if (\is_null($logo)) {
            unset($preferences['logo_id']);
        }
        \wp_send_json_success([
            'preferences' => (object) $preferences,
            // Casting to object ensures empty preferences are seen as an object and not an array
            'logo' => $logo,
        ]);
    }
}
