<?php

namespace Tests\Feature;

use App\Actions\Database\RunBackup;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use JsonException;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider data
     *
     * @throws JsonException
     */
    public function test_create_backup(string $db): void
    {
        SSH::fake();
        Http::fake();

        $this->setupDatabase($db);

        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => \App\Enums\StorageProvider::DROPBOX,
        ]);

        $this->post(route('backups.store', [
            'server' => $this->server,
        ]), [
            'database' => $database->id,
            'storage' => $storage->id,
            'interval' => '0 * * * *',
            'keep' => '10',
        ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('backups', [
            'status' => BackupStatus::RUNNING,
        ]);

        $this->assertDatabaseHas('backup_files', [
            'status' => BackupFileStatus::CREATED,
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_create_custom_interval_backup(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => \App\Enums\StorageProvider::DROPBOX,
        ]);

        $this->post(route('backups.store', ['server' => $this->server]), [
            'database' => $database->id,
            'storage' => $storage->id,
            'interval' => 'custom',
            'custom_interval' => '* * * * *',
            'keep' => '10',
        ])
            ->assertSessionHasNoErrors();

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
            'provider' => \App\Enums\StorageProvider::DROPBOX,
        ]);

        Backup::factory()->create([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $storage->id,
        ]);

        $this->get(route('backups', ['server' => $this->server]))
            ->assertSuccessful();
    }

    /**
     * @throws JsonException
     */
    public function test_update_backup(): void
    {
        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => \App\Enums\StorageProvider::DROPBOX,
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
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'interval' => '0 0 * * *',
            'keep_backups' => 10,
        ]);
    }

    /**
     * @dataProvider data
     *
     * @throws JsonException
     */
    public function test_delete_backup(string $db): void
    {
        $this->setupDatabase($db);

        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => \App\Enums\StorageProvider::DROPBOX,
        ]);

        $backup = Backup::factory()->create([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $storage->id,
        ]);

        $this->delete(route('backups.destroy', ['server' => $this->server, 'backup' => $backup]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('backups', [
            'id' => $backup->id,
        ]);
    }

    /**
     * @dataProvider data
     *
     * @throws JsonException
     */
    public function test_restore_backup(string $db): void
    {
        Http::fake();
        SSH::fake();

        $this->setupDatabase($db);

        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => \App\Enums\StorageProvider::DROPBOX,
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
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('backup_files', [
            'id' => $backupFile->id,
            'status' => BackupFileStatus::RESTORED,
        ]);
    }

    private function setupDatabase(string $database): void
    {
        $this->server->services()->where('type', 'database')->delete();

        $this->server->services()->create([
            'type' => 'database',
            'name' => config('core.databases_name.'.$database),
            'version' => config('core.databases_version.'.$database),
        ]);
    }

    public static function data(): array
    {
        return [
            [\App\Enums\Database::MYSQL80],
            [\App\Enums\Database::MARIADB104],
            [\App\Enums\Database::POSTGRESQL16],
        ];
    }
}
