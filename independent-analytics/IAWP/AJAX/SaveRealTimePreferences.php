<?php

namespace IAWP\AJAX;

use IAWP\Env;
use IAWP\RealTime\RealTime;
/** @internal */
class SaveRealTimePreferences extends \IAWP\AJAX\AJAX
{
    protected function action_name() : string
    {
        return 'iawp_save_real_time_preferences';
    }
    protected function action_callback() : void
    {
        $chart_minutes = $this->get_int_field('chartMinutes');
        $table = $this->get_field('table');
        $group = $this->get_field('group');
        if (\is_int($chart_minutes)) {
            $this->save_chart_minutes($chart_minutes);
        }
        if (\is_string($table) && \is_string($group)) {
            $this->save_group($table, $group);
        }
    }
    private function save_chart_minutes(int $chart_minutes) : void
    {
        if (!\in_array($chart_minutes, RealTime::CHART_MINUTES, \true)) {
            return;
        }
        \update_user_meta(\get_current_user_id(), 'iawp_real_time_chart_minutes', $chart_minutes);
    }
    private function save_group(string $table, string $group) : void
    {
        // Validate the table and group area real combination
        $table_class = Env::get_table($table);
        $table_instance = new $table_class($group);
        $groups = \get_user_meta(\get_current_user_id(), 'iawp_real_time_groups', \true);
        if (!\is_array($groups)) {
            $groups = [];
        }
        $groups[$table_instance->id()] = $table_instance->group()->id();
        $allowed_keys = ['referrers', 'devices', 'geo', 'campaigns'];
        $invalid_keys = \array_diff(\array_keys($groups), $allowed_keys);
        if (!empty($invalid_keys)) {
            return;
        }
        $sanitized = \array_map('sanitize_text_field', $groups);
        \update_user_meta(\get_current_user_id(), 'iawp_real_time_groups', $sanitized);
    }
}
