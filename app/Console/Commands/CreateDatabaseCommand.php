<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDatabaseCommand extends Command
{
    protected $signature = 'database:create';

    protected $description = 'Create the database if it does not exist.';

    public function handle(): void
    {
        $schemaName = config("database.connections.mysql.database");

        config(["database.connections.mysql.database" => null]);

        $query = "CREATE DATABASE IF NOT EXISTS $schemaName";

        DB::statement($query);

        config(["database.connections.mysql.database" => $schemaName]);

        $this->info(sprintf("Database `%s` created successfully.", $schemaName));
    }
}
