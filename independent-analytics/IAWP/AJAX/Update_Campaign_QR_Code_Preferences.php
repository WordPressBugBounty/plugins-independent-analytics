<?php

namespace IAWP\AJAX;

use IAWP\Campaign_QR_Code_Preferences;
/** @internal */
class Update_Campaign_QR_Code_Preferences extends \IAWP\AJAX\AJAX
{
    protected function action_required_fields() : array
    {
        return ['preferences'];
    }
    protected function action_name() : string
    {
        return 'iawp_update_campaign_qr_code_preferences';
    }
    protected function requires_pro() : bool
    {
        return \true;
    }
    protected function action_callback() : void
    {
        if (\is_string($_POST['preferences'])) {
            $changes = \json_decode(\wp_unslash($_POST['preferences']), \true);
            if (\is_array($changes)) {
                (new Campaign_QR_Code_Preferences())->update($changes);
            }
        }
        \wp_send_json_success([]);
    }
}
