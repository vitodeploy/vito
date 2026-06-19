<?php

namespace Tests\Unit\Commands;

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RunBackupCommandTest extends TestCase
{
    use RefreshDatabase;

    private function fakeStorageHttp(): void
    {
        Http::fake([
            '*oauth2/token' => Http::response([
                'access_token' => 'fresh-access',
                'expires_in' => 14400,
            ]),
            '*' => Http::response([], 200),
        ]);
    }

    private function createBackup(array $attributes): Backup
    {
        $database = Database::factory()->create(['server_id' => $this->server]);
        $storage = StorageProvider::factory()->dropbox()->create(['user_id' => $this->user->id]);

        return Backup::factory()->create(array_merge([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $storage->id,
            'keep_backups' => 10,
        ], $attributes));
    }

    public function test_run_without_any_backups(): void
    {
        $this->artisan('backups:run')
            ->expectsOutput('0 backups started');
    }

    public function test_runs_backups_that_are_due(): void
    {
        SSH::fake();
        $this->fakeStorageHttp();
        Carbon::setTestNow('2026-06-19 10:00:00');

        $this->createBackup(['interval' => '0 * * * *']);

        $this->artisan('backups:run')
            ->expectsOutput('1 backups started');
    }

    public function test_does_not_run_backups_that_are_not_due(): void
    {
        SSH::fake();
        Carbon::setTestNow('2026-06-19 10:00:00');

        $this->createBackup(['interval' => '30 * * * *']);

        $this->artisan('backups:run')
            ->expectsOutput('0 backups started');
    }

    public function test_runs_custom_interval_backups_when_due(): void
    {
        SSH::fake();
        $this->fakeStorageHttp();
        Carbon::setTestNow('2026-06-19 10:05:00');

        $this->createBackup(['interval' => '5 10 * * *']);

        $this->artisan('backups:run')
            ->expectsOutput('1 backups started');
    }

    public function test_does_not_run_disabled_backups(): void
    {
        SSH::fake();
        Carbon::setTestNow('2026-06-19 10:00:00');

        $this->createBackup(['interval' => '* * * * *', 'enabled' => false]);

        $this->artisan('backups:run')
            ->expectsOutput('0 backups started');
    }

    public function test_does_not_run_backups_being_deleted(): void
    {
        SSH::fake();
        Carbon::setTestNow('2026-06-19 10:00:00');

        $this->createBackup(['interval' => '* * * * *', 'status' => BackupStatus::DELETING]);

        $this->artisan('backups:run')
            ->expectsOutput('0 backups started');
    }

    public function test_runs_enabled_backup_even_after_a_failed_run(): void
    {
        SSH::fake();
        $this->fakeStorageHttp();
        Carbon::setTestNow('2026-06-19 10:00:00');

        $backup = $this->createBackup(['interval' => '0 * * * *', 'status' => null, 'enabled' => true]);

        BackupFile::factory()->create([
            'backup_id' => $backup->id,
            'status' => BackupFileStatus::FAILED,
        ]);

        $this->artisan('backups:run')
            ->expectsOutput('1 backups started');
    }
}
