<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShowDatabaseStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:structure {--table= : Show structure for a specific table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the current database structure (tables and columns)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = DB::getDefaultConnection();
        $driver = DB::connection()->getDriverName();
        
        $this->info("Database: " . config("database.connections.{$connection}.database"));
        $this->info("Driver: " . strtoupper($driver));
        $this->line("");

        $tableName = $this->option('table');
        
        if ($tableName) {
            $this->showTableStructure($tableName, $driver);
        } else {
            $this->showAllTables($driver);
        }
    }

    /**
     * Show structure for all tables
     */
    protected function showAllTables($driver)
    {
        $tables = $this->getTables($driver);
        
        if (empty($tables)) {
            $this->warn('No tables found in the database.');
            return;
        }

        $this->info("Found " . count($tables) . " table(s):");
        $this->line("");

        foreach ($tables as $table) {
            $this->showTableStructure($table, $driver);
            $this->line("");
        }
    }

    /**
     * Show structure for a specific table
     */
    protected function showTableStructure($tableName, $driver)
    {
        if (!Schema::hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist.");
            return;
        }

        $this->info("Table: <fg=cyan>{$tableName}</>");

        // Show detailed column information
        if ($driver === 'mysql') {
            $this->showMySQLDetails($tableName);
        } elseif ($driver === 'sqlite') {
            $this->showSQLiteDetails($tableName);
        } elseif ($driver === 'pgsql') {
            $this->showPostgreSQLDetails($tableName);
        } else {
            $this->warn("Detailed column information not available for driver: {$driver}");
        }
    }

    /**
     * Get list of tables based on driver
     */
    protected function getTables($driver)
    {
        if ($driver === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            return array_map(function($table) {
                return $table->name;
            }, $tables);
        } elseif ($driver === 'mysql') {
            $database = DB::connection()->getDatabaseName();
            $tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [$database]);
            return array_map(function($table) {
                return $table->TABLE_NAME;
            }, $tables);
        } elseif ($driver === 'pgsql') {
            $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
            return array_map(function($table) {
                return $table->tablename;
            }, $tables);
        }

        return [];
    }

    /**
     * Show MySQL specific details
     */
    protected function showMySQLDetails($tableName)
    {
        $database = DB::connection()->getDatabaseName();
        $columns = DB::select("
            SELECT 
                COLUMN_NAME,
                COLUMN_TYPE,
                IS_NULLABLE,
                COLUMN_DEFAULT,
                COLUMN_KEY,
                EXTRA
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$database, $tableName]);

        if (empty($columns)) {
            $this->warn("No columns found in table '{$tableName}'.");
            return;
        }

        $details = [];
        foreach ($columns as $col) {
            $details[] = [
                'Column' => $col->COLUMN_NAME,
                'Type' => $col->COLUMN_TYPE,
                'Nullable' => $col->IS_NULLABLE,
                'Default' => $col->COLUMN_DEFAULT ?? 'NULL',
                'Key' => $col->COLUMN_KEY,
                'Extra' => $col->EXTRA,
            ];
        }
        $this->table(['Column', 'Type', 'Nullable', 'Default', 'Key', 'Extra'], $details);
    }

    /**
     * Show SQLite specific details
     */
    protected function showSQLiteDetails($tableName)
    {
        $tableNameQuoted = DB::getPdo()->quote($tableName);
        $columns = DB::select("PRAGMA table_info({$tableNameQuoted})");
        
        if (empty($columns)) {
            $this->warn("No columns found in table '{$tableName}'.");
            return;
        }

        $details = [];
        foreach ($columns as $col) {
            $details[] = [
                'Column' => $col->name,
                'Type' => $col->type,
                'Not Null' => $col->notnull ? 'YES' : 'NO',
                'Default' => $col->dflt_value ?? 'NULL',
                'Primary Key' => $col->pk ? 'YES' : 'NO',
            ];
        }
        $this->table(['Column', 'Type', 'Not Null', 'Default', 'Primary Key'], $details);
    }

    /**
     * Show PostgreSQL specific details
     */
    protected function showPostgreSQLDetails($tableName)
    {
        $columns = DB::select("
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default,
                CASE WHEN pk.column_name IS NOT NULL THEN 'YES' ELSE 'NO' END as is_primary_key
            FROM information_schema.columns
            LEFT JOIN (
                SELECT ku.column_name
                FROM information_schema.table_constraints tc
                JOIN information_schema.key_column_usage ku
                    ON tc.constraint_name = ku.constraint_name
                WHERE tc.table_name = ? AND tc.constraint_type = 'PRIMARY KEY'
            ) pk ON information_schema.columns.column_name = pk.column_name
            WHERE table_name = ?
            ORDER BY ordinal_position
        ", [$tableName, $tableName]);

        if (empty($columns)) {
            $this->warn("No columns found in table '{$tableName}'.");
            return;
        }

        $details = [];
        foreach ($columns as $col) {
            $details[] = [
                'Column' => $col->column_name,
                'Type' => $col->data_type,
                'Nullable' => $col->is_nullable,
                'Default' => $col->column_default ?? 'NULL',
                'Primary Key' => $col->is_primary_key,
            ];
        }
        $this->table(['Column', 'Type', 'Nullable', 'Default', 'Primary Key'], $details);
    }
}
