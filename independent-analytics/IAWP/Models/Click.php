<?php

namespace IAWP\Models;

/** @internal */
class Click
{
    protected $row;
    protected $link_name;
    protected $link_target;
    protected $link_clicks;
    public function __construct($row)
    {
        $this->row = $row;
        $this->link_name = $row->link_name;
        $this->link_target = $row->link_target;
        $this->link_clicks = \intval($row->link_clicks);
    }
    public function link_name() : string
    {
        return $this->link_name;
    }
    public function link_target() : ?string
    {
        return $this->link_target;
    }
    public function link_clicks() : int
    {
        return $this->link_clicks;
    }
}
