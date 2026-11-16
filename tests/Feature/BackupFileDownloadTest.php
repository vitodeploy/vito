<?php

use App\Enums\BackupFileStatus;
use App\Enums\BackupType;
use App\Enums\UserRole;
use App\Facades\FTP;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\Server;
use App\Models\StorageProvider;
use App\Models\User;
use App\StorageProviders\Local;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    SSH::fake();
    FTP::swap(Mockery::mock());
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

function backupDownloadStorage(string $provider): StorageProvider
{
    return StorageProvider::factory()->create([
        'provider' => $provider,
        'credentials' => [
            'host' => '1.2.3.4',
            'port' => $provider === 'ftp' ? 21 : 22,
            'path' => '/home/vito',
            'username' => 'username',
            'password' => 'password',
            'ssl' => false,
            'passive' => true,
        ],
    ]);
}

function backupDownloadUrl(Server $server, Backup $backup, BackupFile $file): string
{
    return route('backup-files.download', [
        'server' => $server,
        'backup' => $backup,
        'backupFile' => $file,
    ]);
}

test('downloads a local file backup as a streamed attachment', function (BackupFileStatus $status) {
    SSH::fake('test archive bytes');
    $this->file->update(['status' => $status]);

    $response = $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertOk()
        ->assertDownload('example-20261028065718.tar.gz')
        ->assertHeader('content-type', 'application/octet-stream')
        ->assertHeader('content-length', '18')
        ->assertHeader('x-content-type-options', 'nosniff')
        ->assertHeader('x-accel-buffering', 'no')
        ->assertStreamedContent('test archive bytes');

    expect($response->headers->get('cache-control'))->toContain('private', 'no-store');
    SSH::assertExecutedContains('sftp:stream /home/vito/backups/example.com/example-20261028065718.tar.gz');
    $this->assertDatabaseHas('backup_files', ['id' => $this->file->id, 'status' => $status->value]);
})->with([BackupFileStatus::CREATED, BackupFileStatus::RESTORED, BackupFileStatus::RESTORE_FAILED]);

test('downloads a local database backup with sql.gz extension', function () {
    SSH::fake('sql gzip bytes');

    $database = Database::factory()->create(['server_id' => $this->server->id, 'name' => 'test_db']);
    $this->backup->update([
        'type' => BackupType::DATABASE,
        'database_id' => $database->id,
        'path' => null,
    ]);

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertOk()
        ->assertDownload('example-20261028065718.sql.gz')
        ->assertStreamedContent('sql gzip bytes');

    SSH::assertExecutedContains('sftp:stream /home/vito/backups/test_db/example-20261028065718.sql.gz');
});

test('downloads from SFTP using the storage credentials rather than the managed server', function (string $path, string $resolvedPath) {
    SSH::fake('sftp archive');
    $storage = backupDownloadStorage('sftp');
    $storage->update(['credentials' => [...$storage->credentials, 'path' => $path]]);
    $this->backup->update(['storage_id' => $storage->id]);

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertOk()
        ->assertHeader('content-length', '12')
        ->assertStreamedContent('sftp archive');

    SSH::assertExecutedContains('sftp:connect 1.2.3.4:22 username');
    SSH::assertExecutedContains('sftp:stream '.$resolvedPath.'/example.com/example-20261028065718.tar.gz');
})->with([
    ['/home/vito', '/home/vito'],
    ['backups', '/backups'],
    ['~/backups', 'backups'],
    ['~//backups', 'backups'],
    ['~/../tmp/backups', '/tmp/backups'],
    ['/back%20ups', '/back ups'],
]);

test('downloads from FTP with its SSL and passive settings', function (bool $ssl, bool $passive, string $directory, string $resolvedDirectory) {
    SSH::fake();
    $storage = backupDownloadStorage('ftp');
    $storage->update(['credentials' => [...$storage->credentials, 'ssl' => $ssl, 'passive' => $passive, 'path' => $directory]]);
    $this->backup->update(['storage_id' => $storage->id]);
    $path = $resolvedDirectory.'/example.com/example-20261028065718.tar.gz';

    FTP::shouldReceive('connect')->once()->with('1.2.3.4', 21, $ssl)->andReturn(true);
    FTP::shouldReceive('login')->once()->with('username', 'password', true)->andReturn(true);
    FTP::shouldReceive('passive')->once()->with(true, $passive)->andReturn(true);
    FTP::shouldReceive('size')->once()->with(true, $path)->andReturn(11);
    FTP::shouldReceive('streamDownload')->once()->with(true, $path)->andReturnUsing(function (): void {
        echo 'ftp archive';
    });
    FTP::shouldReceive('close')->once()->with(true);

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertOk()
        ->assertHeader('content-length', '11')
        ->assertStreamedContent('ftp archive');

    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with([
    [false, true, '/home/vito', '/home/vito'],
    [true, false, '/home/vito', '/home/vito'],
    [false, true, '/back%20ups', '/back ups'],
    [false, true, '../backups', 'backups'],
]);

test('FTP missing files are rejected before streaming and close the connection', function () {
    $storage = backupDownloadStorage('ftp');
    $this->backup->update(['storage_id' => $storage->id]);
    FTP::shouldReceive('connect', 'login', 'passive')->andReturn(true);
    FTP::shouldReceive('size')->once()->andReturn(-1);
    FTP::shouldReceive('streamDownload')->never();
    FTP::shouldReceive('close')->once();

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertNotFound();
});

test('FTP failures are not returned as successful downloads', function (string $failure) {
    $storage = backupDownloadStorage('ftp');
    $this->backup->update(['storage_id' => $storage->id]);
    FTP::shouldReceive('connect')->once()->andReturn($failure !== 'connect');
    if ($failure !== 'connect') {
        FTP::shouldReceive('login')->once()->andReturn($failure !== 'login');
        FTP::shouldReceive('close')->once();
    }
    if ($failure === 'passive') {
        FTP::shouldReceive('passive')->once()->andReturn(false);
    }
    FTP::shouldReceive('streamDownload')->never();

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs($this->user)->get(backupDownloadUrl($this->server, $this->backup, $this->file)))
        ->toThrow(RuntimeException::class);
})->with(['connect', 'login', 'passive']);

test('downloads Dropbox archives without exposing a public link', function () {
    $storage = StorageProvider::factory()->dropbox()->create();
    $this->backup->update(['storage_id' => $storage->id]);
    Http::fake([
        '*oauth2/token' => Http::response(['access_token' => 'test-token']),
        'https://content.dropboxapi.com/2/files/download' => Http::response('dropbox archive', 200, [
            'Dropbox-API-Result' => json_encode(['size' => 15]),
        ]),
    ]);

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertOk()
        ->assertHeader('content-length', '15')
        ->assertStreamedContent('dropbox archive');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://content.dropboxapi.com/2/files/download'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && json_decode($request->header('Dropbox-API-Arg')[0], true)['path'] === '/example.com/example-20261028065718.tar.gz');
});

test('Dropbox missing archives return not found', function () {
    $storage = StorageProvider::factory()->dropbox()->create();
    $this->backup->update(['storage_id' => $storage->id]);
    Http::fake([
        '*oauth2/token' => Http::response(['access_token' => 'test-token']),
        '*files/download' => Http::response(['error' => ['.tag' => 'path', 'path' => ['.tag' => 'not_found']]], 409),
    ]);

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertNotFound();
});

test('download fails when the remote backup archive is missing', function (string $provider) {
    SSH::fake()->remoteFileWillBeMissing();
    if ($provider === 'sftp') {
        $storage = backupDownloadStorage('sftp');
        $this->backup->update(['storage_id' => $storage->id]);
    }

    $this->actingAs($this->user)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertNotFound();
    SSH::assertNotExecutedContains('sftp:stream');
})->with(['local', 'sftp']);

test('download rejects unavailable backup files', function (BackupFileStatus $status) {
    SSH::fake();
    $this->file->update(['status' => $status]);

    $this->actingAs($this->user)
        ->getJson(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with([
    BackupFileStatus::CREATING,
    BackupFileStatus::FAILED,
    BackupFileStatus::RESTORING,
    BackupFileStatus::DELETING,
    BackupFileStatus::DELETE_FAILED,
]);

test('download is unavailable for unsupported storage providers', function () {
    SSH::fake();
    $this->backup->storage->update(['provider' => 'uninstalled']);

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
    $otherServer = Server::factory()->create(['project_id' => $this->server->project_id]);
    $this->get(backupDownloadUrl($otherServer, $this->backup, $this->file))->assertNotFound();
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('download enforces project membership', function () {
    SSH::fake();

    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();

    $this->actingAs($otherUser)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertForbidden();
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('read only project members can download backups', function () {
    SSH::fake('archive');
    $member = User::factory()->create(['current_project_id' => $this->server->project_id]);
    $this->server->project->users()->create(['user_id' => $member->id, 'role' => UserRole::USER]);

    $this->actingAs($member)
        ->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertOk()
        ->assertStreamedContent('archive');
});

test('guest cannot download a backup file', function () {
    SSH::fake();

    $this->get(backupDownloadUrl($this->server, $this->backup, $this->file))
        ->assertRedirect();
    expect(SSH::getExecutedCommands())->toBeEmpty();
});
