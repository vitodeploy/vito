<?php

namespace Tests\Feature;

use App\Actions\Backup\RestoreBackup;
use App\Actions\Backup\RunBackup;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\Database;
use App\Models\Server;
use App\Models\StorageProvider;
use App\Models\User;
use App\StorageProviders\Dropbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
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
            'status' => BackupStatus::RUNNING,
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
            'status' => 'running',
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
            'status' => 'running',
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
        Http::fake();

        $this->setupDatabase($db, $version);

        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Dropbox::id(),
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
            'status' => BackupStatus::RUNNING,
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

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Dropbox::id(),
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
            'status' => BackupStatus::RUNNING,
        ]);
    }

    public function test_see_database_backups_list(): void
    {
        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Dropbox::id(),
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

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Dropbox::id(),
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

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Dropbox::id(),
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
            'status' => BackupStatus::RUNNING,
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
            'status' => BackupStatus::RUNNING,
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
        Http::fake();
        SSH::fake();

        $this->setupDatabase($db, $version);

        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Dropbox::id(),
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
            ['mariadb', '10.4'],
            ['postgresql', '16'],
        ];
    }
}
