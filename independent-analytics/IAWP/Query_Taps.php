<?php

namespace IAWP;

use IAWPSCOPED\Illuminate\Database\Query\Builder;
use IAWPSCOPED\Illuminate\Database\Query\JoinClause;
/** @internal */
class Query_Taps
{
    public static function tap_authored_content_check($should_join_resources = \true)
    {
        return function (Builder $query) use($should_join_resources) {
            if (!\is_user_logged_in() || \IAWP\Capability_Manager::can_view_all_analytics()) {
                return;
            }
            if ($should_join_resources) {
                $resources_table = \IAWP\Query::get_table_name(\IAWP\Query::RESOURCES);
                $query->leftJoin($query->raw($resources_table . ' AS resources'), function (JoinClause $join) {
                    $join->on('views.resource_id', '=', 'resources.id');
                });
            }
            $query->where('resources.cached_author_id', '=', \get_current_user_id());
        };
    }
    public static function tap_authored_content_for_clicks()
    {
        return function (Builder $query) {
            if (!\is_user_logged_in() || \IAWP\Capability_Manager::can_view_all_analytics()) {
                return;
            }
            $resources_table = \IAWP\Query::get_table_name(\IAWP\Query::RESOURCES);
            $query->leftJoin($query->raw($resources_table . ' AS resources'), function (JoinClause $join) {
                $join->on('views.resource_id', '=', 'resources.id');
            });
            $query->where('resources.cached_author_id', '=', \get_current_user_id());
        };
    }
    public static function tap_related_to_examined_record(?\IAWP\Examiner_Config $config)
    {
        return function (Builder $query) use($config) {
            if (!$config) {
                return;
            }
            $column = self::examiner_type_to_column($config->group());
            if (!$column) {
                return;
            }
            $query->where($column, '=', $config->id());
        };
    }
    private static function examiner_type_to_column(string $group) : ?string
    {
        switch ($group) {
            case 'page':
                return 'views.resource_id';
            case 'referrer':
                return 'sessions.referrer_id';
            case 'country':
                return 'sessions.country_id';
            case 'city':
                return 'sessions.city_id';
            case 'device_type':
                return 'sessions.device_type_id';
            case 'os':
                return 'sessions.device_os_id';
            case 'browser':
                return 'sessions.device_browser_id';
            case 'campaign':
                return 'sessions.campaign_id';
            case 'link':
                return 'clicks.click_target_id';
            case 'link_pattern':
                return 'clicked_links.link_rule_id';
            default:
                return null;
        }
    }
}
