<?php

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function vitoPestUnitCommandsRunBackupCommandTestFakeStorageHttp(): void
{
    Http::fake([
        '*oauth2/token' => Http::response([
            'access_token' => 'fresh-access',
            'expires_in' => 14400,
        ]),
        '*' => Http::response([], 200),
    ]);
}

/**
 * @param  array{interval: string, enabled?: bool, status?: BackupStatus|null, server_id?: int}  $attributes
 */
function vitoPestUnitCommandsRunBackupCommandTestCreateBackup(array $attributes): Backup
{
    $database = Database::factory()->create(['server_id' => test()->server->id]);
    $storage = StorageProvider::factory()->dropbox()->create(['user_id' => test()->user->id]);

    return Backup::factory()->create(array_merge([
        'server_id' => test()->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
        'keep_backups' => 10,
    ], $attributes));
}

test('run without any backups', function () {
    $this->artisan('backups:run')
        ->expectsOutput('0 backups started');
});

test('runs backups that are due', function () {
    SSH::fake();
    vitoPestUnitCommandsRunBackupCommandTestFakeStorageHttp();
    Carbon::setTestNow('2026-06-19 10:00:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '0 * * * *']);

    $this->artisan('backups:run')
        ->expectsOutput('1 backups started');
});

test('does not run backups that are not due', function () {
    SSH::fake();
    Carbon::setTestNow('2026-06-19 10:00:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '30 * * * *']);

    $this->artisan('backups:run')
        ->expectsOutput('0 backups started');
});

test('runs custom interval backups when due', function () {
    SSH::fake();
    vitoPestUnitCommandsRunBackupCommandTestFakeStorageHttp();
    Carbon::setTestNow('2026-06-19 10:05:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '5 10 * * *']);

    $this->artisan('backups:run')
        ->expectsOutput('1 backups started');
});

test('does not run disabled backups', function () {
    SSH::fake();
    Carbon::setTestNow('2026-06-19 10:00:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '* * * * *', 'enabled' => false]);

    $this->artisan('backups:run')
        ->expectsOutput('0 backups started');
});

test('does not run backups being deleted', function () {
    SSH::fake();
    Carbon::setTestNow('2026-06-19 10:00:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '* * * * *', 'status' => BackupStatus::DELETING]);

    $this->artisan('backups:run')
        ->expectsOutput('0 backups started');
});

test('runs enabled backup even after a failed run', function () {
    SSH::fake();
    vitoPestUnitCommandsRunBackupCommandTestFakeStorageHttp();
    Carbon::setTestNow('2026-06-19 10:00:00');

    $backup = vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '0 * * * *', 'status' => null, 'enabled' => true]);

    BackupFile::factory()->create([
        'backup_id' => $backup->id,
        'status' => BackupFileStatus::FAILED,
    ]);

    $this->artisan('backups:run')
        ->expectsOutput('1 backups started');
});

test('does not run backups whose server is missing', function () {
    SSH::fake();
    Bus::fake();
    Carbon::setTestNow('2026-06-19 10:00:00');

    $backup = vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '0 * * * *', 'server_id' => 999999]);

    $this->artisan('backups:run')
        ->expectsOutput('0 backups started');

    $this->assertDatabaseHas('backups', ['id' => $backup->id, 'server_id' => 999999]);
    Bus::assertNothingDispatched();
});
