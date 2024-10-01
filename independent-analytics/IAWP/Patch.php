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
    public static function patch_2_8_7_potential_duplicates()
    {
        $db_version = \get_option('iawp_db_version', '0');
        $is_migrating = \get_option('iawp_is_migrating', '0') === '1';
        $last_finished_step = \get_option('iawp_last_finished_migration_step', '0');
        $has_error = \get_option('iawp_migration_error_query', null) !== null && \get_option('iawp_migration_error', null) !== null;
        if ($db_version === '36' && $is_migrating && $last_finished_step === '2' && $has_error) {
            \delete_option('iawp_migration_error_original_error_message');
            \delete_option('iawp_last_finished_migration_step');
            \delete_option('iawp_migration_error');
            \delete_option('iawp_migration_error_query');
            \update_option('iawp_is_migrating', '0', \true);
        }
    }
}
