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

    public function test_create_backup(): void
    {
        SSH::fake();
        Http::fake();

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

    public function test_delete_backup(): void
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

        Livewire::test(BackupsList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('delete', $backup->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('backups', [
            'id' => $backup->id,
        ]);
    }

    public function test_restore_backup(): void
    {
        Http::fake();
        SSH::fake();

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
}
