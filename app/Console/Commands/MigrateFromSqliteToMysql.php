<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MigrateFromSqliteToMysql extends Command
{
    protected $signature = 'migrate-from-sqlite-to-mysql {--connection=mysql : Target connection (mysql or mariadb)} {--sqlite-path= : Path to the source SQLite file (defaults to storage/database.sqlite)} {--skip-missing : Skip source tables absent from the target schema instead of aborting} {--force : Do not ask for confirmation}';

    protected $description = 'Migrate Vito\'s database from SQLite to MySQL/MariaDB';

    /**
     * Framework/transient tables that must NOT be copied: the target is freshly migrated
     * (so `migrations` is already correct) and session/cache/queue rows are ephemeral.
     *
     * @var list<string>
     */
    private array $skipTables = [
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    /**
     * The SQLite source path is resolved from --sqlite-path before DB_DATABASE is repurposed for the
     * target; cached config is refused (a cached config never loads .env, so both the target settings
     * and the DB_CONNECTION cut-over would silently no-op); and each table is streamed from a cursor
     * so a large table (e.g. metrics) never has to fit in memory.
     */
    public function handle(): int
    {
        $target = (string) $this->option('connection');

        if (! in_array($target, ['mysql', 'mariadb'], true)) {
            $this->components->error("Invalid --connection '{$target}' — use 'mysql' or 'mariadb'.");

            return self::FAILURE;
        }

        if (App::configurationIsCached()) {
            $this->components->error('Configuration is cached, so your MySQL/MariaDB settings in .env are not loaded.');
            $this->line("  Run 'php artisan config:clear' first, then re-run this command.");

            return self::FAILURE;
        }

        $sqlitePath = (string) ($this->option('sqlite-path') ?: storage_path('database.sqlite'));

        if (! File::exists($sqlitePath) || File::size($sqlitePath) === 0) {
            $this->components->error("No SQLite database found at {$sqlitePath} — nothing to migrate.");

            return self::FAILURE;
        }

        config(['database.connections.sqlite.database' => $sqlitePath]);
        DB::purge('sqlite');

        try {
            DB::connection($target)->getPdo();
        } catch (Throwable $e) {
            $this->components->error("Cannot connect to the {$target} target: ".$e->getMessage());
            $this->line('  Set DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD in your .env first.');

            return self::FAILURE;
        }

        $this->components->warn('Stop the web server, queue workers and scheduler first so nothing writes to SQLite while it is copied.');

        if (! $this->option('force') && ! $this->confirm(
            "This will DROP and rebuild the {$target} database and copy your SQLite data into it. Continue?",
            false,
        )) {
            return self::SUCCESS;
        }

        $this->components->info("Building the {$target} schema...");
        $exitCode = $this->call('migrate:fresh', ['--force' => true, '--database' => $target]);

        if ($exitCode !== self::SUCCESS) {
            $this->components->error("Failed to build the {$target} schema — aborting before any data was copied.");

            return self::FAILURE;
        }

        /** @var list<string> $tables */
        $tables = Schema::connection('sqlite')->getTableListing();

        $this->components->info('Copying data...');
        DB::connection($target)->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tables as $table) {
                if (str_contains($table, '.')) {
                    $table = substr($table, (int) strrpos($table, '.') + 1);
                }

                if (in_array($table, $this->skipTables, true)) {
                    continue;
                }

                if (! Schema::connection($target)->hasTable($table)) {
                    if (! $this->option('skip-missing')) {
                        $this->components->error("Source table `{$table}` has no counterpart in the {$target} schema — aborting. Re-run with --skip-missing to skip absent tables.");

                        return self::FAILURE;
                    }

                    $this->components->warn("Skipping `{$table}` — not present in the target schema.");

                    continue;
                }

                $this->copyTable($target, $table);
            }
        } finally {
            DB::connection($target)->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        if (! $this->switchEnv($target)) {
            $this->components->warn("Data copied to {$target}, but .env could not be updated — set DB_CONNECTION={$target} manually, then restart the queue/scheduler.");

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info("Done. Vito is now running on {$target}. Restart the queue/scheduler if they are running.");

        return self::SUCCESS;
    }

    private function copyTable(string $target, string $table): void
    {
        DB::connection($target)->table($table)->truncate();

        $count = 0;
        $batch = [];

        foreach (DB::connection('sqlite')->table($table)->cursor() as $row) {
            $batch[] = (array) $row;

            if (count($batch) >= 500) {
                DB::connection($target)->table($table)->insert($batch);
                $count += count($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::connection($target)->table($table)->insert($batch);
            $count += count($batch);
        }

        if (Schema::connection($target)->hasColumn($table, 'id')) {
            $max = (int) DB::connection($target)->table($table)->max('id');
            DB::connection($target)->statement("ALTER TABLE `{$table}` AUTO_INCREMENT = ".($max + 1));
        }

        $this->components->twoColumnDetail("  {$table}", (string) $count.' rows');
    }

    private function switchEnv(string $target): bool
    {
        $path = base_path('.env');

        if (! File::exists($path)) {
            return false;
        }

        $env = File::get($path);

        if (preg_match('/^DB_CONNECTION=/m', $env) === 1) {
            $env = (string) preg_replace('/^DB_CONNECTION=.*$/m', "DB_CONNECTION={$target}", $env);
        } else {
            $env = rtrim($env)."\nDB_CONNECTION={$target}\n";
        }

        return File::put($path, $env) !== false;
    }
}
