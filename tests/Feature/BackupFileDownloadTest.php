<?php

use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\Server;
use App\Models\StorageProvider;
use App\Models\User;
use App\StorageProviders\Local;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $storage = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => Local::id(),
        'credentials' => ['path' => '/home/vito/backups'],
    ]);

    $this->backup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $this->server->id,
        'storage_id' => $storage->id,
        'path' => '/home/vito/example.com',
    ]);

    $this->file = BackupFile::factory()->create([
        'backup_id' => $this->backup->id,
        'name' => 'example-20261028065718',
        'status' => BackupFileStatus::CREATED,
    ]);
});

function backupDownloadUrl(Server $server, Backup $backup, BackupFile $file): string
{
    return route('backup-files.download', [
        'server' => $server,
        'backup' => $backup,
        'backupFile' => $file,
    ]);
}

test('downloads a local file backup as a streamed attachment', function () {
    SSH::fake('test archive bytes');

    $response = $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertOk()
        ->assertHeader('content-type', 'application/octet-stream')
        ->assertHeader('x-content-type-options', 'nosniff');

    expect($response->headers->get('content-disposition'))
        ->toContain('attachment')
        ->toContain('example-20261028065718.tar.gz');
    expect($response->streamedContent())->toBe('test archive bytes');

    SSH::assertExecutedContains('sftp:stream /home/vito/backups/example.com/example-20261028065718.tar.gz');
});

test('downloads a local database backup with sql.gz extension', function () {
    SSH::fake('sql gzip bytes');

    $database = Database::factory()->create(['server_id' => $this->server->id, 'name' => 'test_db']);
    $this->backup->update([
        'type' => BackupType::DATABASE,
        'database_id' => $database->id,
        'path' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('.sql.gz');
    expect($response->streamedContent())->toBe('sql gzip bytes');
    SSH::assertExecutedContains('sftp:stream /home/vito/backups/test_db/example-20261028065718.sql.gz');
});

test('download fails when the remote backup archive is missing', function () {
    SSH::fake()->remoteFileWillBeMissing();

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertNotFound();
});

test('download rejects unavailable backup files', function (BackupFileStatus $status) {
    SSH::fake();
    $this->file->update(['status' => $status]);

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertStatus(409);
})->with([
    BackupFileStatus::CREATING,
    BackupFileStatus::FAILED,
    BackupFileStatus::RESTORING,
    BackupFileStatus::DELETING,
    BackupFileStatus::DELETE_FAILED,
]);

test('download is unavailable for unsupported storage providers', function () {
    SSH::fake();

    $storage = StorageProvider::factory()->dropbox()->create(['user_id' => $this->user->id]);
    $this->backup->update(['storage_id' => $storage->id]);

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertNotFound();
});

test('download checks the server and backup file relationship', function () {
    SSH::fake();

    $otherBackup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'storage_id' => $this->backup->storage_id,
        'type' => BackupType::FILE,
        'path' => '/home/vito/other.com',
    ]);

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $otherBackup, $this->file))
        ->assertNotFound();
});

test('download enforces project membership', function () {
    SSH::fake();

    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();

    $this->actingAs($otherUser)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertForbidden();
});

test('guest cannot download a backup file', function () {
    SSH::fake();

    $this->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertRedirect();
});
