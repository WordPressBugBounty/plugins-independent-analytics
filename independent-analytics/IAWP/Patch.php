<?php

namespace IAWP;

/** @internal */
class Patch
{
    public static function patch_2_6_2_incorrect_email_report_schedule()
    {
        if (\IAWPSCOPED\iawp_is_pro() && \get_option('iawp_patch_2_6_2_applied', '0') === '0') {
            if (!\is_null(\IAWPSCOPED\iawp()->email_reports->next_event_scheduled_at())) {
                \IAWPSCOPED\iawp()->email_reports->schedule();
            }
            \update_option('iawp_patch_2_6_2_applied', '1', \true);
        }
    }
}
