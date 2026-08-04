<?php

namespace IAWP\RealTime;

use IAWP\Date_Range\Exact_Date_Range;
use IAWP\Examiner_Config;
use IAWP\Illuminate_Builder;
use IAWP\Query_Taps;
use IAWP\Tables;
/** @internal */
class RecentVisitors
{
    private int $visitors;
    public function __construct(Exact_Date_Range $range, ?Examiner_Config $examiner_config)
    {
        $this->visitors = \intval(Illuminate_Builder::new()->selectRaw('COUNT(DISTINCT sessions.visitor_id) AS visitors')->from(Tables::views(), 'views')->leftJoin(Tables::sessions() . ' AS sessions', 'sessions.session_id', '=', 'views.session_id')->tap(Query_Taps::tap_related_to_examined_record($examiner_config))->whereBetween('viewed_at', [$range->iso_start(), $range->iso_end()])->value('visitors'));
    }
    public function visitors() : int
    {
        return $this->visitors;
    }
}
