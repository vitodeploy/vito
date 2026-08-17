<?php

use App\Actions\Backup\RunBackup;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        ->expectsOutput('0 backups started, 0 failed');
});

test('runs backups that are due', function () {
    SSH::fake();
    vitoPestUnitCommandsRunBackupCommandTestFakeStorageHttp();
    Carbon::setTestNow('2026-06-19 10:00:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '0 * * * *']);

    $this->artisan('backups:run')
        ->expectsOutput('1 backups started, 0 failed');
});

test('does not run backups that are not due', function () {
    SSH::fake();
    Carbon::setTestNow('2026-06-19 10:00:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '30 * * * *']);

    $this->artisan('backups:run')
        ->expectsOutput('0 backups started, 0 failed');
});

test('runs custom interval backups when due', function () {
    SSH::fake();
    vitoPestUnitCommandsRunBackupCommandTestFakeStorageHttp();
    Carbon::setTestNow('2026-06-19 10:05:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '5 10 * * *']);

    $this->artisan('backups:run')
        ->expectsOutput('1 backups started, 0 failed');
});

test('does not run disabled backups', function () {
    SSH::fake();
    Carbon::setTestNow('2026-06-19 10:00:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '* * * * *', 'enabled' => false]);

    $this->artisan('backups:run')
        ->expectsOutput('0 backups started, 0 failed');
});

test('does not run backups being deleted', function () {
    SSH::fake();
    Carbon::setTestNow('2026-06-19 10:00:00');

    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '* * * * *', 'status' => BackupStatus::DELETING]);

    $this->artisan('backups:run')
        ->expectsOutput('0 backups started, 0 failed');
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
        ->expectsOutput('1 backups started, 0 failed');
});

test('continues to the next backup when one fails', function () {
    SSH::fake();
    Log::spy();
    Carbon::setTestNow('2026-06-19 10:00:00');

    $first = vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '0 * * * *']);
    vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '0 * * * *']);

    $calls = 0;
    $this->mock(RunBackup::class, function ($mock) use (&$calls): void {
        $mock->shouldReceive('run')
            ->twice()
            ->andReturnUsing(function (Backup $backup) use (&$calls): BackupFile {
                $calls++;

                if ($calls === 1) {
                    throw new RuntimeException('boom');
                }

                return BackupFile::factory()->create([
                    'backup_id' => $backup->id,
                    'status' => BackupFileStatus::CREATED,
                ]);
            });
    });

    $this->artisan('backups:run')
        ->expectsOutput('1 backups started, 1 failed');

    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $message, array $context): bool => $context['backup_id'] === $first->id
            && $context['server_id'] === $this->server->id
            && $context['error'] === 'boom'
    );
});

test('does not run backups whose server is missing', function () {
    DB::statement('PRAGMA defer_foreign_keys = ON');
    SSH::fake();
    Bus::fake();
    Carbon::setTestNow('2026-06-19 10:00:00');

    $backup = vitoPestUnitCommandsRunBackupCommandTestCreateBackup(['interval' => '0 * * * *', 'server_id' => 999999]);

    $this->artisan('backups:run')
        ->expectsOutput('0 backups started, 0 failed');

    $this->assertDatabaseHas('backups', ['id' => $backup->id, 'server_id' => 999999]);
    Bus::assertNothingDispatched();
});
