<?php

use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Facades\SSH;
use App\Jobs\Backup\DeleteFileJob;
use App\Jobs\Backup\DeleteJob;
use App\Jobs\Backup\RestoreDatabaseJob;
use App\Jobs\Backup\RestoreFileJob;
use App\Jobs\Backup\RunJob;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\StorageProvider;
use App\Notifications\RestoreFailed;
use App\StorageProviders\Dropbox;
use App\StorageProviders\S3;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->storageProvider = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => Dropbox::id(),
    ]);

    $this->backup = Backup::factory()->create([
        'type' => BackupType::DATABASE,
        'server_id' => $this->server->id,
        'storage_id' => $this->storageProvider->id,
        'database_id' => Database::factory()->create(['server_id' => $this->server->id])->id,
        'status' => null,
    ]);

    $this->backupFile = BackupFile::factory()->create([
        'backup_id' => $this->backup->id,
        'status' => BackupFileStatus::CREATING,
    ]);
});

test('run job failed sets file to failed and logs', function () {
    SSH::fake();

    $job = new RunJob($this->backupFile, $this->backup);
    $job->failed(new Exception('Backup failed'));

    $this->backup->refresh();
    $this->backupFile->refresh();

    expect($this->backup->status)->toBeNull();
    expect($this->backupFile->status)->toEqual(BackupFileStatus::FAILED);
    expect($this->backupFile->message)->toBe('Backup failed');

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'run-backup-failed',
    ]);
});

test('restore database job failed sends notification', function () {
    SSH::fake();
    Notification::fake();

    $database = Database::factory()->create(['server_id' => $this->server->id]);
    $this->backupFile->update(['status' => BackupFileStatus::RESTORING]);

    $job = new RestoreDatabaseJob($this->backupFile, $database);
    $job->failed(new Exception('Restore failed'));

    Notification::assertSentTo($this->notificationChannel, RestoreFailed::class);
});

test('delete file keeps row when remote delete fails', function () {
    SSH::fake();

    $storage = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => S3::id(),
        'credentials' => [
            'key' => 'k',
            'secret' => 's',
            'region' => 'us-east-1',
            'bucket' => 'b',
            'path' => 'backups',
        ],
    ]);

    $backup = Backup::factory()->create([
        'type' => BackupType::DATABASE,
        'server_id' => $this->server->id,
        'storage_id' => $storage->id,
        'database_id' => Database::factory()->create(['server_id' => $this->server->id])->id,
    ]);

    $file = BackupFile::factory()->create([
        'backup_id' => $backup->id,
        'status' => BackupFileStatus::DELETING,
    ]);

    $file->deleteFile();

    $this->assertDatabaseHas('backup_files', [
        'id' => $file->id,
        'status' => BackupFileStatus::DELETE_FAILED->value,
    ]);
});

test('reconcile marks stuck creating files as failed', function () {
    Notification::fake();

    $this->backupFile->update(['status' => BackupFileStatus::CREATING]);
    BackupFile::query()
        ->whereKey($this->backupFile->id)
        ->update(['updated_at' => now()->subSeconds(2 * (int) config('core.backup_run_timeout') + 60)]);

    $this->artisan('backups:reconcile')->assertSuccessful();

    $this->assertDatabaseHas('backup_files', [
        'id' => $this->backupFile->id,
        'status' => BackupFileStatus::FAILED->value,
    ]);
});

test('restore database job failed sets restore failed and logs', function () {
    SSH::fake();

    $database = Database::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->backupFile->update(['status' => BackupFileStatus::RESTORING]);

    $job = new RestoreDatabaseJob($this->backupFile, $database);
    $job->failed(new Exception('Restore failed'));

    $this->backupFile->refresh();

    expect($this->backupFile->status)->toEqual(BackupFileStatus::RESTORE_FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'restore-database-failed',
    ]);
});

test('restore file job failed sets restore failed and logs', function () {
    SSH::fake();

    $fileBackup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $this->server->id,
        'storage_id' => $this->storageProvider->id,
        'path' => '/home/vito/app',
        'status' => null,
    ]);

    $file = BackupFile::factory()->create([
        'backup_id' => $fileBackup->id,
        'status' => BackupFileStatus::RESTORING,
    ]);

    $job = new RestoreFileJob($file, '/home/vito/restored', 'vito:vito', '755');
    $job->failed(new Exception('Restore failed'));

    $file->refresh();

    expect($file->status)->toEqual(BackupFileStatus::RESTORE_FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'restore-file-failed',
    ]);
});

test('delete job failed logs', function () {
    $job = new DeleteJob($this->backup);
    $job->failed(new Exception('Delete failed'));

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'delete-backup-failed',
    ]);
});

test('delete file job failed logs', function () {
    $job = new DeleteFileJob($this->backupFile);
    $job->failed(new Exception('Delete file failed'));

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'delete-backup-file-failed',
    ]);
});
