<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowDatabaseData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:data {--table= : Show data for a specific table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the current database data (table contents)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tableName = $this->option('table');
        
        if ($tableName) {
            $this->showTableData($tableName);
        } else {
            $this->showAllTablesData();
        }
    }

    /**
     * Show data for all tables
     */
    protected function showAllTablesData()
    {
        $tables = $this->getTables();
        
        if (empty($tables)) {
            $this->warn('No tables found in the database.');
            return;
        }

        foreach ($tables as $table) {
            $this->showTableData($table);
            $this->line("");
        }
    }

    /**
     * Show data for a specific table
     */
    protected function showTableData($tableName)
    {
        if (!DB::getSchemaBuilder()->hasTable($tableName)) {
            $this->error("Table '{$tableName}' does not exist.");
            return;
        }

        $count = DB::table($tableName)->count();
        $this->info("Table: <fg=cyan>{$tableName}</> ({$count} row(s))");
        
        if ($count === 0) {
            $this->warn("  Table is empty.");
            return;
        }

        $data = DB::table($tableName)->get();
        
        // Get column names
        $columns = array_keys((array) $data->first());
        
        // Prepare table data
        $tableData = [];
        foreach ($data as $row) {
            $rowArray = [];
            foreach ($columns as $col) {
                $value = $row->$col;
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (is_null($value)) {
                    $value = '<fg=gray>NULL</>';
                } elseif (is_string($value) && strlen($value) > 50) {
                    $value = substr($value, 0, 50) . '...';
                }
                $rowArray[] = $value;
            }
            $tableData[] = $rowArray;
        }

        $this->table($columns, $tableData);
    }

    /**
     * Get list of tables
     */
    protected function getTables()
    {
        $driver = DB::connection()->getDriverName();
        
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
}
