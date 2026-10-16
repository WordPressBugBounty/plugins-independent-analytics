<?php

namespace IAWP\Migrations;

/** @internal */
class Migration_53 extends \IAWP\Migrations\Step_Migration
{
    /**
     * @return int
     */
    protected function database_version() : int
    {
        return 53;
    }
    /**
     * @return array
     */
    protected function queries() : array
    {
        return [$this->add_note_column_to_campaign_urls()];
    }
    private function add_note_column_to_campaign_urls() : string
    {
        return "\n            ALTER TABLE {$this->tables::campaign_urls()} \n                ADD COLUMN note VARCHAR(2048) AFTER utm_content;\n        ";
    }
}
