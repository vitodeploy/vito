<?php

namespace Tests\Feature;

use App\Actions\Database\RunBackup;
use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider;
use App\Web\Pages\Servers\Databases\Backups;
use App\Web\Pages\Servers\Databases\Widgets\BackupFilesList;
use App\Web\Pages\Servers\Databases\Widgets\BackupsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider data
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

        Livewire::test(Backups::class, ['server' => $this->server])
            ->callAction('create', [
                'database' => $database->id,
                'storage' => $storage->id,
                'interval' => '0 * * * *',
                'keep' => '10',
            ])
            ->assertSuccessful();

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
            'provider' => \App\Enums\StorageProvider::DROPBOX,
        ]);

        Livewire::test(Backups::class, ['server' => $this->server])
            ->callAction('create', [
                'database' => $database->id,
                'storage' => $storage->id,
                'interval' => 'custom',
                'custom_interval' => '* * * * *',
                'keep' => '10',
            ])
            ->assertSuccessful();

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

        $backup = Backup::factory()->create([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $storage->id,
        ]);

        $this->get(
            Backups::getUrl([
                'server' => $this->server,
                'backup' => $backup,
            ])
        )
            ->assertSuccessful()
            ->assertSee($backup->database->name);
    }

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

        Livewire::test(BackupsList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('edit', $backup->id, [
                'interval' => '0 0 * * *',
                'keep' => '10',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('backups', [
            'id' => $backup->id,
            'interval' => '0 0 * * *',
            'keep_backups' => 10,
        ]);
    }

    /**
     * @dataProvider data
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

        Livewire::test(BackupsList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('delete', $backup->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('backups', [
            'id' => $backup->id,
        ]);
    }

    /**
     * @dataProvider data
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

        Livewire::test(BackupFilesList::class, [
            'server' => $this->server,
            'backup' => $backup,
        ])
            ->callTableAction('restore', $backupFile->id, [
                'database' => $database->id,
            ])
            ->assertSuccessful();

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
