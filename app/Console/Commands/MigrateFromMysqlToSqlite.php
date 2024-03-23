<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateFromMysqlToSqlite extends Command
{
    protected $signature = 'migrate-from-mysql-to-sqlite';

    protected $description = 'Migrate from Mysql to SQLite';

    public function handle(): void
    {
        $this->info('Migrating from Mysql to SQLite...');

        File::exists(storage_path('database.sqlite'))
            ? File::delete(storage_path('database.sqlite'))
            : null;

        File::put(storage_path('database.sqlite'), '');

        config(['database.default' => 'sqlite']);

        $this->call('migrate', ['--force' => true]);

        $this->migrateModel(\App\Models\Backup::class);
        $this->migrateModel(\App\Models\BackupFile::class);
        $this->migrateModel(\App\Models\CronJob::class);
        $this->migrateModel(\App\Models\Database::class);
        $this->migrateModel(\App\Models\DatabaseUser::class);
        $this->migrateModel(\App\Models\Deployment::class);
        $this->migrateModel(\App\Models\DeploymentScript::class);
        $this->migrateModel(\App\Models\FirewallRule::class);
        $this->migrateModel(\App\Models\GitHook::class);
        $this->migrateModel(\App\Models\NotificationChannel::class);
        $this->migrateModel(\App\Models\Project::class);
        $this->migrateModel(\App\Models\Queue::class);
        $this->migrateModel(\App\Models\Server::class);
        $this->migrateModel(\App\Models\ServerLog::class);
        $this->migrateModel(\App\Models\ServerProvider::class);
        $this->migrateModel(\App\Models\Service::class);
        $this->migrateModel(\App\Models\Site::class);
        $this->migrateModel(\App\Models\SourceControl::class);
        $this->migrateModel(\App\Models\SshKey::class);
        $this->migrateModel(\App\Models\Ssl::class);
        $this->migrateModel(\App\Models\StorageProvider::class);
        $this->migrateModel(\App\Models\User::class);

        $env = File::get(base_path('.env'));
        $env = str_replace('DB_CONNECTION=mysql', 'DB_CONNECTION=sqlite', $env);
        $env = str_replace('DB_DATABASE=vito', '', $env);
        File::put(base_path('.env'), $env);

        $this->info('Migrated from Mysql to SQLite');
    }

    private function migrateModel(string $model): void
    {
        $this->info("Migrating model: {$model}");

        config(['database.default' => 'mysql']);

        $rows = $model::where('id', '>', 0)->get();

        foreach ($rows as $row) {
            DB::connection('sqlite')->table($row->getTable())->insert($row->getAttributes());
        }

        $this->info("Migrated model: {$model}");
    }
}
