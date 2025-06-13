<?php

namespace Tests\Feature;

use App\Actions\Database\RunBackup;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider;
use App\StorageProviders\Dropbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('data')]
    public function test_create_backup(string $db, string $version): void
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

    public function test_create_custom_interval_backup(): void
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

    public function test_see_backups_list(): void
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

    public function test_update_backup(): void
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
    public function test_delete_backup(string $db, string $version): void
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

    #[DataProvider('data')]
    public function test_restore_backup(string $db, string $version): void
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
