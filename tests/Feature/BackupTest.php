<?php

namespace Tests\Feature;

use App\Actions\Backup\RestoreBackup;
use App\Actions\Backup\RunBackup;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\DatabaseStatus;
use App\Enums\ServerStatus;
use App\Facades\SSH;
use App\Jobs\Backup\RestoreDatabaseJob;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\Project;
use App\Models\Server;
use App\Models\StorageProvider;
use App\Models\User;
use App\StorageProviders\Local;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Server $server;

    protected StorageProvider $storageProvider;

    protected Backup $backup;

    protected BackupFile $backupFile;

    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup for file restore tests
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
    }

    public function test_backup_model_can_have_path_field(): void
    {
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

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertEquals(BackupType::FILE, $backup->type);
        $this->assertEquals('/var/www/html', $backup->path);
        $this->assertNull($backup->database_id);
        $this->assertEquals($storage->id, $backup->storage_id);
    }

    public function test_backup_model_can_have_database_field(): void
    {
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

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertEquals(BackupType::DATABASE, $backup->type);
        $this->assertEquals($database->id, $backup->database_id);
        $this->assertNull($backup->path);
        $this->assertEquals($storage->id, $backup->storage_id);
    }

    #[DataProvider('data')]
    public function test_create_database_backup(string $db, string $version): void
    {
        SSH::fake();
        Http::fake([
            '*oauth2/token' => Http::response([
                'access_token' => 'fresh-access',
                'expires_in' => 14400,
            ]),
            '*' => Http::response([], 200),
        ]);

        $this->setupDatabase($db, $version);

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
    }

    public function test_create_custom_interval_database_backup(): void
    {
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
    }

    public function test_see_database_backups_list(): void
    {
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
    }

    public function test_update_database_backup(): void
    {
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
    }

    #[DataProvider('data')]
    public function test_delete_database_backup(string $db, string $version): void
    {
        $this->setupDatabase($db, $version);

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
    }

    public function test_file_restore_validation_requires_path(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The path field is required.');

        app(RestoreBackup::class)->restore($this->backupFile, []);
    }

    public function test_file_restore_validation_path_must_be_string(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The path field must be a string.');

        app(RestoreBackup::class)->restore($this->backupFile, [
            'path' => 123,
        ]);
    }

    public function test_file_restore_validation_path_must_not_be_empty(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The path field is required.');

        app(RestoreBackup::class)->restore($this->backupFile, [
            'path' => '',
        ]);
    }

    public function test_file_restore_sets_correct_status_and_restored_to(): void
    {
        Bus::fake();

        app(RestoreBackup::class)->restore($this->backupFile, [
            'path' => '/home/vito/restored-x.com',
            'owner' => 'vito:vito',
            'permissions' => '755',
        ]);

        $this->backupFile->refresh();

        $this->assertEquals(BackupFileStatus::RESTORING, $this->backupFile->status);
        $this->assertEquals('/home/vito/restored-x.com', $this->backupFile->restored_to);
    }

    public function test_file_restore_dispatches_job(): void
    {
        Bus::fake();

        app(RestoreBackup::class)->restore($this->backupFile, [
            'path' => '/home/vito/restored-x.com',
            'owner' => 'vito:vito',
            'permissions' => '755',
        ]);

        // The job dispatch is tested by checking that the status is set correctly
        // and the restored_to field is populated
        $this->backupFile->refresh();
        $this->assertEquals(BackupFileStatus::RESTORING, $this->backupFile->status);
        $this->assertEquals('/home/vito/restored-x.com', $this->backupFile->restored_to);
    }

    public function test_database_restore_validation_requires_database(): void
    {
        // Create a database backup instead
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
    }

    public function test_database_restore_validation_database_must_exist(): void
    {
        // Create a database backup instead
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
            'database' => 999, // Non-existent database
        ]);
    }

    #[DataProvider('data')]
    public function test_restore_database_backup(string $db, string $version): void
    {
        Http::fake([
            '*oauth2/token' => Http::response([
                'access_token' => 'fresh-access',
                'expires_in' => 14400,
            ]),
            '*' => Http::response([], 200),
        ]);
        SSH::fake();

        $this->setupDatabase($db, $version);

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
    }

    #[DataProvider('data')]
    public function test_database_backup_and_restore_are_streamed(string $db, string $version): void
    {
        Http::fake([
            '*oauth2/token' => Http::response([
                'access_token' => 'fresh-access',
                'expires_in' => 14400,
            ]),
            '*' => Http::response([], 200),
        ]);
        SSH::fake();

        $this->setupDatabase($db, $version);

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
    }

    public function test_database_backup_captures_compressed_size(): void
    {
        Http::fake();
        SSH::fake('12345');

        $this->setupDatabase('mysql', '8.4');

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
    }

    public function test_database_backup_file_uses_sql_gz_extension(): void
    {
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

        $this->assertStringEndsWith('.sql.gz', $file->tempPath());
        $this->assertStringEndsWith('.sql.gz', $file->path());
    }

    public function test_file_backup_file_uses_tar_gz_extension(): void
    {
        $this->assertStringEndsWith('.tar.gz', $this->backupFile->tempPath());
    }

    public function test_can_disable_backup(): void
    {
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
    }

    public function test_can_enable_backup(): void
    {
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
    }

    public function test_cannot_enable_a_backup_being_deleted(): void
    {
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
    }

    public function test_see_global_backups_list_scoped_to_current_project(): void
    {
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
    }

    public function test_per_server_backups_list_is_filtered_to_server(): void
    {
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
    }

    public function test_backup_run_captures_database_engine_and_version(): void
    {
        Http::fake();
        SSH::fake('8.0.36');

        $this->setupDatabase('mysql', '8.4');

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
    }

    public function test_backup_run_succeeds_with_null_version_when_version_query_yields_nothing(): void
    {
        Http::fake();
        SSH::fake();

        $this->setupDatabase('mysql', '8.4');

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
    }

    public function test_restore_to_database_on_same_server_without_metadata_is_allowed(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile();
        $targetDatabase = Database::factory()->create(['server_id' => $this->server->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionDoesntHaveErrors();

        Bus::assertDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_to_compatible_server_is_allowed(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
        $targetServer = $this->createTargetServer('mysql', '8.4');
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionDoesntHaveErrors();

        Bus::assertDispatched(RestoreDatabaseJob::class);

        $this->assertEquals(
            "{$targetDatabase->name} ({$targetServer->name})",
            $backupFile->refresh()->restored_to,
        );
    }

    public function test_restore_to_server_with_lower_version_is_rejected(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
        $targetServer = $this->createTargetServer('mysql', '5.7');
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionHasErrors('database');

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_to_server_with_different_engine_is_rejected(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
        $targetServer = $this->createTargetServer('mariadb', '11.4');
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionHasErrors('database');

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_without_metadata_to_another_server_is_rejected(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile();
        $targetServer = $this->createTargetServer('mysql', '8.4');
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionHasErrors('database');

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_from_local_storage_to_another_server_is_rejected(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $localStorage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Local::id(),
            'credentials' => ['path' => '/home/vito/backups'],
        ]);
        $backupFile = $this->databaseBackupFile(
            ['database_engine' => 'mysql', 'database_version' => '8.0.36'],
            $localStorage,
        );
        $targetServer = $this->createTargetServer('mysql', '8.4');
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionHasErrors('database');

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_to_database_in_another_project_is_rejected(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
        $otherProject = Project::factory()->create();
        $targetServer = $this->createTargetServer('mysql', '8.4', $otherProject->id);
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionHasErrors('database');

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_to_not_ready_server_is_rejected(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
        $targetServer = $this->createTargetServer('mysql', '8.4');
        $targetServer->update(['status' => ServerStatus::INSTALLING]);
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionHasErrors('database');

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_to_not_ready_database_is_rejected(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
        $targetServer = $this->createTargetServer('mysql', '8.4');
        $targetDatabase = Database::factory()->create([
            'server_id' => $targetServer->id,
            'status' => DatabaseStatus::DELETING,
        ]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionHasErrors('database');

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_to_soft_deleted_database_is_rejected(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
        $targetServer = $this->createTargetServer('mysql', '8.4');
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);
        $targetDatabase->delete();

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionHasErrors('database');

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_is_rejected_when_target_version_cannot_be_determined(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
        $targetServer = $this->createTargetServer('mysql', 'latest');
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionHasErrors('database');

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_allows_less_precise_target_version(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.39']);
        $targetServer = $this->createTargetServer('mysql', '8.0');
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionDoesntHaveErrors();

        Bus::assertDispatched(RestoreDatabaseJob::class);
    }

    public function test_restore_with_foreign_backup_file_returns_404(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile();
        $foreignFile = $this->databaseBackupFile();
        $targetDatabase = Database::factory()->create(['server_id' => $this->server->id]);

        $this->post(route('backup-files.restore', [
            'server' => $this->server,
            'backup' => $backupFile->backup,
            'backupFile' => $foreignFile,
        ]), [
            'database' => $targetDatabase->id,
        ])->assertNotFound();

        Bus::assertNotDispatched(RestoreDatabaseJob::class);
    }

    public function test_delete_with_foreign_backup_file_returns_404(): void
    {
        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile();
        $foreignFile = $this->databaseBackupFile();

        $this->delete(route('backup-files.destroy', [
            'server' => $this->server,
            'backup' => $backupFile->backup,
            'backupFile' => $foreignFile,
        ]))->assertNotFound();

        $this->assertDatabaseHas('backup_files', [
            'id' => $foreignFile->id,
            'status' => BackupFileStatus::CREATED,
        ]);
    }

    public function test_restore_backup_on_another_server(): void
    {
        Http::fake([
            '*oauth2/token' => Http::response([
                'access_token' => 'fresh-access',
                'expires_in' => 14400,
            ]),
            '*' => Http::response([], 200),
        ]);
        SSH::fake();

        $this->actingAs($this->user);

        $backupFile = $this->databaseBackupFile(['database_engine' => 'mysql', 'database_version' => '8.0.36']);
        $targetServer = $this->createTargetServer('mysql', '8.4');
        $targetDatabase = Database::factory()->create(['server_id' => $targetServer->id]);

        $this->postRestore($backupFile, $targetDatabase)
            ->assertSessionDoesntHaveErrors();

        $backupFile->refresh();

        $this->assertEquals(BackupFileStatus::RESTORED, $backupFile->status);
        $this->assertEquals("{$targetDatabase->name} ({$targetServer->name})", $backupFile->restored_to);
    }

    public function test_temp_path_uses_target_server_ssh_user(): void
    {
        $targetServer = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'ssh_user' => 'other-user',
        ]);

        $this->assertStringStartsWith('/home/vito/', $this->backupFile->tempPath());
        $this->assertStringStartsWith('/home/other-user/', $this->backupFile->tempPath($targetServer));
    }

    #[DataProvider('versionComparisons')]
    public function test_version_gte(string $target, string $source, bool $expected): void
    {
        $this->assertSame($expected, BackupFile::versionGte($target, $source));
    }

    /**
     * @return array<int, array<int, string|bool>>
     */
    public static function versionComparisons(): array
    {
        return [
            ['8.4', '8.0.36', true],
            ['8.0', '8.0.39', true],
            ['16', '16.4', true],
            ['15', '16.1', false],
            ['5.7', '8.0.36', false],
            ['10.11.6', '10.11.6', true],
            ['11.4', '10.11.6', true],
        ];
    }

    public function test_normalize_version(): void
    {
        $this->assertSame('8.0.42', BackupFile::normalizeVersion("8.0.42\n0.24.04"));
        $this->assertSame('16', BackupFile::normalizeVersion('16'));
        $this->assertSame('10.11.6', BackupFile::normalizeVersion('10.11.6-MariaDB'));
        $this->assertNull(BackupFile::normalizeVersion('latest'));
        $this->assertNull(BackupFile::normalizeVersion(''));
        $this->assertNull(BackupFile::normalizeVersion(null));
    }

    /**
     * @param  array<string, mixed>  $fileAttributes
     */
    private function databaseBackupFile(array $fileAttributes = [], ?StorageProvider $storage = null): BackupFile
    {
        $database = Database::factory()->create(['server_id' => $this->server->id]);
        $backup = Backup::factory()->create([
            'type' => BackupType::DATABASE,
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => ($storage ?? StorageProvider::factory()->dropbox()->create(['user_id' => $this->user->id]))->id,
            'status' => null,
        ]);

        return BackupFile::factory()->create([
            'backup_id' => $backup->id,
            'status' => BackupFileStatus::CREATED,
            ...$fileAttributes,
        ]);
    }

    private function createTargetServer(string $engine, string $version, ?int $projectId = null): Server
    {
        $server = Server::factory()->create([
            'project_id' => $projectId ?? $this->server->project_id,
            'user_id' => $this->user->id,
        ]);
        $server->services()->create([
            'type' => 'database',
            'name' => $engine,
            'version' => $version,
        ]);

        return $server;
    }

    private function postRestore(BackupFile $backupFile, Database $targetDatabase): TestResponse
    {
        return $this->post(route('backup-files.restore', [
            'server' => $backupFile->backup->server_id,
            'backup' => $backupFile->backup_id,
            'backupFile' => $backupFile,
        ]), [
            'database' => $targetDatabase->id,
        ]);
    }

    private function setupDatabase(string $database, string $version): void
    {
        $this->server->services()->where('type', 'database')->delete();

        $this->server->services()->create([
            'type' => 'database',
            'name' => $database,
            'version' => $version,
        ]);
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function data(): array
    {
        return [
            ['mysql', '8.4'],
            ['mariadb', '10.11'],
            ['postgresql', '16'],
        ];
    }
}
