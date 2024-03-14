<?php

namespace Tests\Feature;

use App\Enums\BackupFileStatus;
use App\Enums\BackupStatus;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

        $this->post(route('servers.databases.backups.store', $this->server), [
            'backup_database' => $database->id,
            'backup_storage' => $storage->id,
            'backup_interval' => '0 * * * *',
            'backup_keep' => '10',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('backups', [
            'status' => BackupStatus::RUNNING,
        ]);

        $this->assertDatabaseHas('backup_files', [
            'status' => BackupFileStatus::CREATED,
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

        $this->get(route('servers.databases.backups', [$this->server, $backup]))
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

        $this->delete(route('servers.databases.backups.destroy', [$this->server, $backup]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('backups', [
            'id' => $backup->id,
        ]);
    }
}
