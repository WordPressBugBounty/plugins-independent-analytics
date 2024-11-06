<?php

namespace IAWP;

/** @internal */
class Sort_Configuration
{
    public const ASCENDING = 'asc';
    public const DESCENDING = 'desc';
    public const VALID_DIRECTIONS = [self::ASCENDING, self::DESCENDING];
    public const DEFAULT_DIRECTION = self::DESCENDING;
    private $direction = self::DEFAULT_DIRECTION;
    private $column;
    private $is_column_nullable;
    public function __construct(string $column, ?string $direction = null, bool $is_column_nullable = \false)
    {
        $this->column = $column;
        if (\in_array($direction, self::VALID_DIRECTIONS)) {
            $this->direction = $direction;
        }
        $this->is_column_nullable = $is_column_nullable;
    }
    public function column() : string
    {
        return $this->column;
    }
    public function direction() : string
    {
        return $this->direction;
    }
    public function is_column_nullable() : bool
    {
        return $this->direction === self::ASCENDING && $this->is_column_nullable;
    }
}
