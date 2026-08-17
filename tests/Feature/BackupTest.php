<?php

use App\Actions\Backup\ManageBackupFile;
use App\Actions\Backup\RestoreBackup;
use App\Actions\Backup\RunBackup;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\DatabaseStatus;
use App\Enums\ServerStatus;
use App\Facades\SSH;
use App\Jobs\Backup\DeleteFileJob;
use App\Jobs\Backup\RestoreDatabaseJob;
use App\Jobs\Backup\RestoreFileJob;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\Project;
use App\Models\Server;
use App\Models\StorageProvider;
use App\StorageProviders\Local;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->storageProvider = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
    ]);
    $this->backup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $this->server->id,
        'storage_id' => $this->storageProvider->id,
        'path' => '/home/vito/x.com',
        'status' => null,
    ]);
    $this->backupFile = BackupFile::factory()->create([
        'backup_id' => $this->backup->id,
        'status' => BackupFileStatus::CREATED,
    ]);
});

test('backup model can have path field', function () {
    $server = Server::factory()->create();
    $storage = StorageProvider::factory()->create();

    $backup = Backup::create([
        'type' => BackupType::FILE,
        'server_id' => $server->id,
        'storage_id' => $storage->id,
        'path' => '/var/www/html',
        'interval' => '0 0 * * *',
        'keep_backups' => 5,
        'status' => null,
    ]);

    expect($backup)->toBeInstanceOf(Backup::class);
    expect($backup->type)->toEqual(BackupType::FILE);
    expect($backup->path)->toEqual('/var/www/html');
    expect($backup->database_id)->toBeNull();
    expect($backup->storage_id)->toEqual($storage->id);
});

test('backup model can have database field', function () {
    $server = Server::factory()->create();
    $storage = StorageProvider::factory()->create();
    $database = $server->databases()->create([
        'name' => 'test_db',
        'status' => 'ready',
    ]);

    $backup = Backup::create([
        'type' => BackupType::DATABASE,
        'server_id' => $server->id,
        'storage_id' => $storage->id,
        'database_id' => $database->id,
        'interval' => '0 0 * * *',
        'keep_backups' => 5,
        'status' => null,
    ]);

    expect($backup)->toBeInstanceOf(Backup::class);
    expect($backup->type)->toEqual(BackupType::DATABASE);
    expect($backup->database_id)->toEqual($database->id);
    expect($backup->path)->toBeNull();
    expect($backup->storage_id)->toEqual($storage->id);
});

test('create database backup', function (string $db, string $version) {
    SSH::fake();
    Http::fake([
        '*oauth2/token' => Http::response([
            'access_token' => 'fresh-access',
            'expires_in' => 14400,
        ]),
        '*' => Http::response([], 200),
    ]);

    vitoPestFeatureBackupTestSetupDatabase($db, $version);

    $this->actingAs($this->user);

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $this->post(route('backups.store', [
        'server' => $this->server,
    ]), [
        'type' => 'database',
        'database' => $database->id,
        'storage' => $storage->id,
        'interval' => '0 * * * *',
        'keep' => '10',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('backups', [
        'status' => null,
    ]);

    $this->assertDatabaseHas('backup_files', [
        'status' => BackupFileStatus::CREATED,
    ]);
})->with('data');

test('create custom interval database backup', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $this->post(route('backups.store', ['server' => $this->server]), [
        'type' => 'database',
        'database' => $database->id,
        'storage' => $storage->id,
        'interval' => 'custom',
        'custom_interval' => '* * * * *',
        'keep' => '10',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('backups', [
        'status' => null,
    ]);
});

test('see database backups list', function () {
    $this->actingAs($this->user);

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
    ]);

    $this->get(route('backups', ['server' => $this->server]))
        ->assertSuccessful();
});

test('update database backup', function () {
    $this->actingAs($this->user);

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $backup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
        'interval' => '0 * * * *',
        'keep_backups' => 5,
    ]);

    $this->patch(route('backups.update', [
        'server' => $this->server,
        'backup' => $backup,
    ]), [
        'interval' => '0 0 * * *',
        'keep' => 10,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('backups', [
        'id' => $backup->id,
        'interval' => '0 0 * * *',
        'keep_backups' => 10,
    ]);
});

test('delete database backup', function (string $db, string $version) {
    vitoPestFeatureBackupTestSetupDatabase($db, $version);

    $this->actingAs($this->user);

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $backup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
    ]);

    $this->delete(route('backups.destroy', ['server' => $this->server, 'backup' => $backup]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('backups', [
        'id' => $backup->id,
    ]);
})->with('data');

test('file restore validation requires path', function () {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The path field is required.');

    app(RestoreBackup::class)->restore($this->backupFile, []);
});

test('file restore validation path must be string', function () {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The path field must be a string.');

    app(RestoreBackup::class)->restore($this->backupFile, [
        'path' => 123,
    ]);
});

test('file restore validation path must not be empty', function () {
    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The path field is required.');

    app(RestoreBackup::class)->restore($this->backupFile, [
        'path' => '',
    ]);
});

test('file restore sets correct status and restored to', function () {
    Bus::fake();

    app(RestoreBackup::class)->restore($this->backupFile, [
        'path' => '/home/vito/restored-x.com',
        'owner' => 'vito:vito',
        'permissions' => '755',
    ]);

    $this->backupFile->refresh();

    expect($this->backupFile->status)->toEqual(BackupFileStatus::RESTORING);
    expect($this->backupFile->restored_to)->toEqual('/home/vito/restored-x.com');
});

test('file restore dispatches job', function () {
    Bus::fake();

    app(RestoreBackup::class)->restore($this->backupFile, [
        'path' => '/home/vito/restored-x.com',
        'owner' => 'vito:vito',
        'permissions' => '755',
    ]);

    Bus::assertDispatched(RestoreFileJob::class);
});

test('database restore validation requires database', function () {
    $databaseBackup = Backup::factory()->create([
        'type' => BackupType::DATABASE,
        'server_id' => $this->server->id,
        'storage_id' => $this->storageProvider->id,
        'database_id' => 1,
        'status' => null,
    ]);
    $databaseBackupFile = BackupFile::factory()->create([
        'backup_id' => $databaseBackup->id,
        'status' => BackupFileStatus::CREATED,
    ]);

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The database field is required.');

    app(RestoreBackup::class)->restore($databaseBackupFile, []);
});

test('database restore validation database must exist', function () {
    $databaseBackup = Backup::factory()->create([
        'type' => BackupType::DATABASE,
        'server_id' => $this->server->id,
        'storage_id' => $this->storageProvider->id,
        'database_id' => 1,
        'status' => null,
    ]);
    $databaseBackupFile = BackupFile::factory()->create([
        'backup_id' => $databaseBackup->id,
        'status' => BackupFileStatus::CREATED,
    ]);

    $this->expectException(ValidationException::class);
    $this->expectExceptionMessage('The selected database is invalid.');

    app(RestoreBackup::class)->restore($databaseBackupFile, [
        'database' => 999,
    ]);
});

test('restore database backup', function (string $db, string $version) {
    Http::fake([
        '*oauth2/token' => Http::response([
            'access_token' => 'fresh-access',
            'expires_in' => 14400,
        ]),
        '*' => Http::response([], 200),
    ]);
    SSH::fake();

    vitoPestFeatureBackupTestSetupDatabase($db, $version);

    $this->actingAs($this->user);

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $backup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
    ]);

    $backupFile = app(RunBackup::class)->run($backup);

    $this->post(route('backup-files.restore', [
        'server' => $this->server,
        'backup' => $backup,
        'backupFile' => $backupFile,
    ]), [
        'database' => $database->id,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('backup_files', [
        'id' => $backupFile->id,
        'status' => BackupFileStatus::RESTORED,
    ]);
})->with('data');

test('database backup and restore are streamed', function (string $db, string $version) {
    Http::fake([
        '*oauth2/token' => Http::response([
            'access_token' => 'fresh-access',
            'expires_in' => 14400,
        ]),
        '*' => Http::response([], 200),
    ]);
    SSH::fake();

    vitoPestFeatureBackupTestSetupDatabase($db, $version);

    $this->actingAs($this->user);

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $backup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
    ]);

    $backupFile = app(RunBackup::class)->run($backup);

    SSH::assertExecutedContains('| ');
    SSH::assertExecutedContains('gzip');
    SSH::assertNotExecutedContains('unzip');

    $this->post(route('backup-files.restore', [
        'server' => $this->server,
        'backup' => $backup,
        'backupFile' => $backupFile,
    ]), [
        'database' => $database->id,
    ])
        ->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains('gunzip -c');
})->with('data');

test('database backup captures compressed size', function () {
    Http::fake();
    SSH::fake('12345');

    vitoPestFeatureBackupTestSetupDatabase('mysql', '8.4');

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => Local::id(),
        'credentials' => ['path' => '/home/vito/backups'],
    ]);

    $backup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
    ]);

    $backupFile = app(RunBackup::class)->run($backup);

    $this->assertDatabaseHas('backup_files', [
        'id' => $backupFile->id,
        'status' => BackupFileStatus::CREATED,
        'size' => 12345,
    ]);
});

test('database backup file uses sql gz extension', function () {
    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->dropbox()->create([
        'user_id' => $this->user->id,
    ]);

    $backup = Backup::factory()->create([
        'type' => BackupType::DATABASE,
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
    ]);

    $file = BackupFile::factory()->create([
        'backup_id' => $backup->id,
        'name' => 'db-20260101000000',
    ]);

    expect($file->tempPath())->toEndWith('.sql.gz');
    expect($file->path())->toEndWith('.sql.gz');
});

test('file backup file uses tar gz extension', function () {
    expect($this->backupFile->tempPath())->toEndWith('.tar.gz');
});

test('can disable backup', function () {
    $this->actingAs($this->user);

    $backup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $this->server->id,
        'storage_id' => $this->storageProvider->id,
        'path' => '/home/vito/x.com',
        'status' => null,
        'enabled' => true,
    ]);

    $this->post(route('backups.disable', ['server' => $this->server, 'backup' => $backup]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('backups', [
        'id' => $backup->id,
        'enabled' => false,
        'status' => null,
    ]);
});

test('can enable backup', function () {
    $this->actingAs($this->user);

    $backup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $this->server->id,
        'storage_id' => $this->storageProvider->id,
        'path' => '/home/vito/x.com',
        'status' => null,
        'enabled' => false,
    ]);

    $this->post(route('backups.enable', ['server' => $this->server, 'backup' => $backup]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('backups', [
        'id' => $backup->id,
        'enabled' => true,
        'status' => null,
    ]);
});

test('cannot enable a backup being deleted', function () {
    $this->actingAs($this->user);

    $backup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $this->server->id,
        'storage_id' => $this->storageProvider->id,
        'path' => '/home/vito/x.com',
        'status' => BackupStatus::DELETING,
        'enabled' => false,
    ]);

    $this->post(route('backups.enable', ['server' => $this->server, 'backup' => $backup]))
        ->assertSessionHasErrors('backup');

    $this->assertDatabaseHas('backups', [
        'id' => $backup->id,
        'enabled' => false,
        'status' => BackupStatus::DELETING->value,
    ]);
});

test('delete orphaned backup file hard deletes without dispatching job', function () {
    DB::statement('PRAGMA defer_foreign_keys = ON');
    Bus::fake();

    $backup = Backup::factory()->create([
        'type' => BackupType::DATABASE,
        'server_id' => 999999,
        'storage_id' => $this->storageProvider->id,
        'status' => null,
    ]);
    $file = BackupFile::factory()->create([
        'backup_id' => $backup->id,
        'status' => BackupFileStatus::CREATED,
    ]);

    app(ManageBackupFile::class)->delete($file);

    $this->assertDatabaseMissing('backup_files', ['id' => $file->id]);
    Bus::assertNotDispatched(DeleteFileJob::class);
});

test('deleting a server row cascades to its backups and backup files', function () {
    $server = Server::factory()->create([
        'project_id' => $this->user->currentProject->id,
        'user_id' => $this->user->id,
    ]);
    $backup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $server->id,
        'storage_id' => $this->storageProvider->id,
        'path' => '/home/vito/y.com',
        'status' => null,
    ]);
    $file = BackupFile::factory()->create([
        'backup_id' => $backup->id,
        'status' => BackupFileStatus::CREATED,
    ]);

    DB::table('servers')->where('id', $server->id)->delete();

    $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
    $this->assertDatabaseMissing('backup_files', ['id' => $file->id]);
});

test('see global backups list scoped to current project', function () {
    $this->actingAs($this->user);

    $secondServer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
    ]);
    $secondBackup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $secondServer->id,
        'storage_id' => $this->storageProvider->id,
        'path' => '/home/vito/y.com',
        'status' => null,
    ]);

    $otherProject = Project::factory()->create();
    $otherServer = Server::factory()->create([
        'project_id' => $otherProject->id,
        'user_id' => $this->user->id,
    ]);
    $otherBackup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $otherServer->id,
        'storage_id' => $this->storageProvider->id,
        'path' => '/home/vito/z.com',
        'status' => null,
    ]);

    $this->get(route('backups.all'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('backups/index')
            ->where('backups.data', function ($rows) use ($secondBackup, $otherBackup): bool {
                $ids = collect($rows)->pluck('id');

                return $ids->contains($this->backup->id)
                    && $ids->contains($secondBackup->id)
                    && ! $ids->contains($otherBackup->id);
            })
        );
});

test('per server backups list is filtered to server', function () {
    $this->actingAs($this->user);

    $secondServer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
    ]);
    $secondBackup = Backup::factory()->create([
        'type' => BackupType::FILE,
        'server_id' => $secondServer->id,
        'storage_id' => $this->storageProvider->id,
        'path' => '/home/vito/y.com',
        'status' => null,
    ]);

    $this->get(route('backups', ['server' => $secondServer]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('backups/index')
            ->where('backups.data', function ($rows) use ($secondBackup): bool {
                $ids = collect($rows)->pluck('id');

                return $ids->contains($secondBackup->id) && ! $ids->contains($this->backup->id);
            })
        );
});

test('backup run captures database engine and version', function () {
    Http::fake();
    SSH::fake('8.0.36');

    vitoPestFeatureBackupTestSetupDatabase('mysql', '8.4');

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => Local::id(),
        'credentials' => ['path' => '/home/vito/backups'],
    ]);

    $backup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
    ]);

    $backupFile = app(RunBackup::class)->run($backup);

    $this->assertDatabaseHas('backup_files', [
        'id' => $backupFile->id,
        'status' => BackupFileStatus::CREATED,
        'database_engine' => 'mysql',
        'database_version' => '8.0.36',
    ]);
});

test('backup run succeeds with null version when version query yields nothing', function () {
    Http::fake();
    SSH::fake();

    vitoPestFeatureBackupTestSetupDatabase('mysql', '8.4');

    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $storage = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => Local::id(),
        'credentials' => ['path' => '/home/vito/backups'],
    ]);

    $backup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'database_id' => $database->id,
        'storage_id' => $storage->id,
    ]);

    $backupFile = app(RunBackup::class)->run($backup);

    $this->assertDatabaseHas('backup_files', [
        'id' => $backupFile->id,
        'status' => BackupFileStatus::CREATED,
        'database_engine' => 'mysql',
        'database_version' => null,
    ]);
});

test('restore to database on same server without metadata is allowed', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile();
    $targetDatabase = Database::factory()->create(['server_id' => $this->server->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionDoesntHaveErrors();

    Bus::assertDispatched(RestoreDatabaseJob::class);
});

test('restore to compatible server is allowed', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '8.4');
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionDoesntHaveErrors();

    Bus::assertDispatched(RestoreDatabaseJob::class);

    expect($backupFile->refresh()->restored_to)->toEqual("{$targetDatabase->name} ({$targetServer->name})");
});

test('restore to server with lower version is rejected', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '5.7');
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionHasErrors('database');

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('restore to server with different engine is rejected', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mariadb', '11.4');
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionHasErrors('database');

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('restore without metadata to another server is rejected', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile();
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '8.4');
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionHasErrors('database');

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('restore from local storage to another server is rejected', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $localStorage = StorageProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => Local::id(),
        'credentials' => ['path' => '/home/vito/backups'],
    ]);
    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36'], $localStorage);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '8.4');
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionHasErrors('database');

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('restore to database in another project is rejected', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
    $otherProject = Project::factory()->create();
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '8.4', $otherProject->id);
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionHasErrors('database');

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('restore to not ready server is rejected', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '8.4');
    $targetServer->update(['status' => ServerStatus::INSTALLING]);
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionHasErrors('database');

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('restore to not ready database is rejected', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '8.4');
    $targetDatabase = Database::factory()->create([
        'server_id' => $targetServer->id,
        'status' => DatabaseStatus::DELETING,
    ]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionHasErrors('database');

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('restore to soft deleted database is rejected', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '8.4');
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);
    $targetDatabase->delete();

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionHasErrors('database');

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('restore is rejected when target version cannot be determined', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', 'latest');
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionHasErrors('database');

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('restore allows less precise target version', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.39']);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '8.0');
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionDoesntHaveErrors();

    Bus::assertDispatched(RestoreDatabaseJob::class);
});

test('restore with foreign backup file returns 404', function () {
    Bus::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile();
    $foreignFile = vitoPestFeatureBackupTestDatabaseBackupFile();
    $targetDatabase = Database::factory()->create(['server_id' => $this->server->id]);

    $this->post(route('backup-files.restore', [
        'server' => $this->server,
        'backup' => $backupFile->backup,
        'backupFile' => $foreignFile,
    ]), [
        'database' => $targetDatabase->id,
    ])->assertNotFound();

    Bus::assertNotDispatched(RestoreDatabaseJob::class);
});

test('delete with foreign backup file returns 404', function () {
    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile();
    $foreignFile = vitoPestFeatureBackupTestDatabaseBackupFile();

    $this->delete(route('backup-files.destroy', [
        'server' => $this->server,
        'backup' => $backupFile->backup,
        'backupFile' => $foreignFile,
    ]))->assertNotFound();

    $this->assertDatabaseHas('backup_files', [
        'id' => $foreignFile->id,
        'status' => BackupFileStatus::CREATED,
    ]);
});

test('restore backup on another server', function () {
    Http::fake([
        '*oauth2/token' => Http::response([
            'access_token' => 'fresh-access',
            'expires_in' => 14400,
        ]),
        '*' => Http::response([], 200),
    ]);
    SSH::fake();

    $this->actingAs($this->user);

    $backupFile = vitoPestFeatureBackupTestDatabaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
    $targetServer = vitoPestFeatureBackupTestCreateTargetServer('mysql', '8.4');
    $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

    vitoPestFeatureBackupTestPostRestore($backupFile, $targetDatabase)
        ->assertSessionDoesntHaveErrors();

    $backupFile->refresh();

    expect($backupFile->status)->toEqual(BackupFileStatus::RESTORED);
    expect($backupFile->restored_to)->toEqual("{$targetDatabase->name} ({$targetServer->name})");
});

test('temp path uses target server ssh user', function () {
    $targetServer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'ssh_user' => 'other-user',
    ]);

    expect($this->backupFile->tempPath())->toStartWith('/home/vito/');
    expect($this->backupFile->tempPath($targetServer))->toStartWith('/home/other-user/');
});

test('version gte', function (string $target, string $source, bool $expected) {
    expect(BackupFile::versionGte($target, $source))->toBe($expected);
})->with('versionComparisons');

/**
 * @return array<int, array<int, string|bool>>
 */
dataset('versionComparisons', function () {
    return [
        ['8.4', '8.0.36', true],
        ['8.0', '8.0.39', true],
        ['16', '16.4', true],
        ['15', '16.1', false],
        ['5.7', '8.0.36', false],
        ['10.11.6', '10.11.6', true],
        ['11.4', '10.11.6', true],
    ];
});

test('normalize version', function () {
    expect(BackupFile::normalizeVersion("8.0.42\n0.24.04"))->toBe('8.0.42');
    expect(BackupFile::normalizeVersion('16'))->toBe('16');
    expect(BackupFile::normalizeVersion('10.11.6-MariaDB'))->toBe('10.11.6');
    expect(BackupFile::normalizeVersion('latest'))->toBeNull();
    expect(BackupFile::normalizeVersion(''))->toBeNull();
    expect(BackupFile::normalizeVersion(null))->toBeNull();
});

/**
 * @param  array<string, mixed>  $fileAttributes
 */
function vitoPestFeatureBackupTestDatabaseBackupFile(array $fileAttributes = [], ?StorageProvider $storage = null): BackupFile
{
    $database = Database::factory()->create(['server_id' => test()->server->id]);
    $backup = Backup::factory()->create([
        'type' => BackupType::DATABASE,
        'server_id' => test()->server->id,
        'database_id' => $database->id,
        'storage_id' => ($storage ?? StorageProvider::factory()->dropbox()->create(['user_id' => test()->user->id]))->id,
        'status' => null,
    ]);

    return BackupFile::factory()->create([
        'backup_id' => $backup->id,
        'status' => BackupFileStatus::CREATED,
        ...$fileAttributes,
    ]);
}

function vitoPestFeatureBackupTestCreateTargetServer(string $engine, string $version, ?int $projectId = null): Server
{
    $server = Server::factory()->create([
        'project_id' => $projectId ?? test()->server->project_id,
        'user_id' => test()->user->id,
    ]);
    $server->services()->create([
        'type' => 'database',
        'name' => $engine,
        'version' => $version,
    ]);

    return $server;
}

function vitoPestFeatureBackupTestPostRestore(BackupFile $backupFile, Database $targetDatabase): TestResponse
{
    return test()->post(route('backup-files.restore', [
        'server' => $backupFile->backup->server_id,
        'backup' => $backupFile->backup_id,
        'backupFile' => $backupFile,
    ]), [
        'database' => $targetDatabase->id,
    ]);
}

function vitoPestFeatureBackupTestSetupDatabase(string $database, string $version): void
{
    test()->server->services()->where('type', 'database')->delete();

    test()->server->services()->create([
        'type' => 'database',
        'name' => $database,
        'version' => $version,
    ]);
}

/**
 * @return array<int, array<int, string>>
 */
dataset('data', function () {
    return [
        ['mysql', '8.4'],
        ['mariadb', '10.11'],
        ['postgresql', '16'],
    ];
});
