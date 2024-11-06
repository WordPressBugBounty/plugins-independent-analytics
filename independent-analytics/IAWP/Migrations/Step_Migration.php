<?php

namespace IAWP\Migrations;

use IAWP\Database;
use IAWP\Query;
use IAWP\Tables;
/** @internal */
abstract class Step_Migration
{
    protected $tables = Tables::class;
    protected abstract function database_version() : int;
    protected abstract function queries() : array;
    public function migrate() : bool
    {
        $current_db_version = \get_option('iawp_db_version', '0');
        if (\version_compare($current_db_version, \strval($this->database_version()), '>=')) {
            return \true;
        }
        $completed = $this->run_queries();
        if ($completed) {
            \update_option('iawp_db_version', $this->database_version(), \true);
        }
        return $completed;
    }
    public function character_set() : string
    {
        return Database::character_set();
    }
    public function collation() : string
    {
        return Database::collation();
    }
    protected function drop_table_if_exists(string $table_name) : string
    {
        return "\n            DROP TABLE IF EXISTS {$table_name};\n        ";
    }
    private function run_queries() : bool
    {
        global $wpdb;
        $queries = $this->queries();
        foreach ($queries as $index => $query) {
            // Skip the step if there is no query to run
            if (\is_null($query)) {
                \update_option('iawp_last_finished_migration_step', $index + 1, \true);
                continue;
            }
            $initial_response = $wpdb->query($query);
            if ($initial_response === \false) {
                \sleep(1);
                \update_option('iawp_migration_error_original_error_message', \trim($wpdb->last_error), \true);
                $is_connected = $wpdb->check_connection(\false);
                if (!$is_connected) {
                    // There is no database connection at this point, so options cannot be updated
                    return \false;
                }
                $retry_response = $wpdb->query($query);
                if ($retry_response === \false) {
                    // You cannot take these variable values and inline them below. The calls to
                    // update_option use $wpdb, so last_error and last_query will be altered
                    $last_error = \trim($wpdb->last_error);
                    $last_query = \trim($wpdb->last_query);
                    \update_option('iawp_migration_error', $last_error, \true);
                    \update_option('iawp_migration_error_query', $last_query, \true);
                    return \false;
                }
            }
            \update_option('iawp_last_finished_migration_step', $index + 1, \true);
        }
        return \true;
    }
}
