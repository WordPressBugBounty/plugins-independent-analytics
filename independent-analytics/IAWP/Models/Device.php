<?php

namespace IAWP\Models;

/** @internal */
class Device
{
    use \IAWP\Models\Universal_Model_Columns;
    protected $row;
    private $type;
    private $os;
    private $browser;
    public function __construct($row)
    {
        $this->row = $row;
        $this->type = $row->device_type ?? null;
        $this->os = $row->os ?? null;
        $this->browser = $row->browser ?? null;
    }
    public function device_type()
    {
        return $this->type;
    }
    public function browser()
    {
        return $this->browser;
    }
    public function os()
    {
        return $this->os;
    }
}
