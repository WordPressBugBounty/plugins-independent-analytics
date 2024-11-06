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
            $views_table = \IAWP\Query::get_table_name(\IAWP\Query::VIEWS);
            $query->leftJoin($query->raw($views_table . ' AS views'), function (JoinClause $join) {
                $join->on('clicks.view_id', '=', 'views.id');
            });
            $resources_table = \IAWP\Query::get_table_name(\IAWP\Query::RESOURCES);
            $query->leftJoin($query->raw($resources_table . ' AS resources'), function (JoinClause $join) {
                $join->on('views.resource_id', '=', 'resources.id');
            });
            $query->where('resources.cached_author_id', '=', \get_current_user_id());
        };
    }
}
