<?php

namespace IAWP;

use IAWP\Tables\Table_Campaigns;
use IAWP\Tables\Table_Clicks;
use IAWP\Tables\Table_Devices;
use IAWP\Tables\Table_Geo;
use IAWP\Tables\Table_Pages;
use IAWP\Tables\Table_Referrers;
/** @internal */
class Env
{
    public function is_free() : bool
    {
        return \IAWPSCOPED\iawp_is_free();
    }
    public function is_pro() : bool
    {
        return \IAWPSCOPED\iawp_is_pro();
    }
    public function is_white_labeled() : bool
    {
        return \IAWP\Capability_Manager::show_white_labeled_ui();
    }
    public function can_write() : bool
    {
        return \IAWP\Capability_Manager::can_edit();
    }
    public static function get_page() : ?string
    {
        if (!\is_admin()) {
            return null;
        }
        $page = $_GET['page'] ?? null;
        $valid_pages = ['independent-analytics', 'independent-analytics-settings', 'independent-analytics-campaign-builder', 'independent-analytics-click-tracking', 'independent-analytics-support-center', 'independent-analytics-updates'];
        if (\in_array($page, $valid_pages)) {
            return $page;
        }
        return null;
    }
    public static function get_tab() : ?string
    {
        if (self::get_page() !== 'independent-analytics') {
            return null;
        }
        if (\IAWPSCOPED\iawp_is_pro()) {
            $valid_tabs = ['views', 'referrers', 'geo', 'devices', 'campaigns', 'clicks', 'real-time'];
        } else {
            $valid_tabs = ['views', 'referrers', 'geo', 'devices'];
        }
        $default_tab = $valid_tabs[0];
        $tab = \array_key_exists('tab', $_GET) ? \stripslashes(\sanitize_text_field($_GET['tab'])) : \false;
        $is_valid = \array_search($tab, $valid_tabs) != \false;
        if (!$tab || !$is_valid) {
            $tab = $default_tab;
        }
        return $tab;
    }
    public static function get_table(?string $table_type = null) : string
    {
        if (\array_key_exists('tab', $_GET)) {
            $table_type = \stripslashes(\sanitize_text_field($_GET['tab']));
        }
        if (\array_key_exists('table_type', $_POST)) {
            $table_type = \stripslashes(\sanitize_text_field($_POST['table_type']));
        }
        switch ($table_type) {
            case 'views':
            default:
                return Table_Pages::class;
            case 'referrers':
                return Table_Referrers::class;
            case 'geo':
                return Table_Geo::class;
            case 'devices':
                return Table_Devices::class;
            case 'campaigns':
                return Table_Campaigns::class;
            case 'clicks':
                return Table_Clicks::class;
        }
    }
}
