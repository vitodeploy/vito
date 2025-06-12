<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\CronJob;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Models\Deployment;
use App\Models\DeploymentScript;
use App\Models\FirewallRule;
use App\Models\GitHook;
use App\Models\NotificationChannel;
use App\Models\Project;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\ServerProvider;
use App\Models\Service;
use App\Models\Site;
use App\Models\SourceControl;
use App\Models\SshKey;
use App\Models\Ssl;
use App\Models\StorageProvider;
use App\Models\User;
use App\Models\Worker;
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

        if (File::exists(storage_path('database.sqlite'))) {
            File::delete(storage_path('database.sqlite'));
        }

        File::put(storage_path('database.sqlite'), '');

        config(['database.default' => 'sqlite']);

        $this->call('migrate', ['--force' => true]);

        $this->migrateModel(Backup::class);
        $this->migrateModel(BackupFile::class);
        $this->migrateModel(CronJob::class);
        $this->migrateModel(Database::class);
        $this->migrateModel(DatabaseUser::class);
        $this->migrateModel(Deployment::class);
        $this->migrateModel(DeploymentScript::class);
        $this->migrateModel(FirewallRule::class);
        $this->migrateModel(GitHook::class);
        $this->migrateModel(NotificationChannel::class);
        $this->migrateModel(Project::class);
        $this->migrateModel(Worker::class);
        $this->migrateModel(Server::class);
        $this->migrateModel(ServerLog::class);
        $this->migrateModel(ServerProvider::class);
        $this->migrateModel(Service::class);
        $this->migrateModel(Site::class);
        $this->migrateModel(SourceControl::class);
        $this->migrateModel(SshKey::class);
        $this->migrateModel(Ssl::class);
        $this->migrateModel(StorageProvider::class);
        $this->migrateModel(User::class);

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
