<?php

namespace IAWP\Utils;

/** @internal */
class CSV
{
    private $header;
    private $rows;
    /**
     * @param array $header
     * @param array[] $rows
     */
    public function __construct(array $header, array $rows)
    {
        $this->header = $header;
        $this->rows = $rows;
    }
    public function to_string() : string
    {
        $delimiter = ',';
        $enclosure = '"';
        $escape_character = '\\';
        $temporary_file = \fopen('php://memory', 'r+');
        \fputcsv($temporary_file, $this->header, $delimiter, $enclosure, $escape_character);
        foreach ($this->rows as $row) {
            \fputcsv($temporary_file, $row, $delimiter, $enclosure, $escape_character);
        }
        \rewind($temporary_file);
        return \stream_get_contents($temporary_file);
    }
}
