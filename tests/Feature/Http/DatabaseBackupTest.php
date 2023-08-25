<?php

namespace Tests\Feature\Http;

use App\Enums\BackupStatus;
use App\Facades\SSH;
use App\Http\Livewire\Databases\DatabaseBackups;
use App\Jobs\Backup\RunBackup;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_backup(): void
    {
        $this->actingAs($this->user);

        Bus::fake();

        SSH::fake()->outputShouldBe('test');

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => \App\Enums\StorageProvider::DROPBOX
        ]);

        Livewire::test(DatabaseBackups::class, ['server' => $this->server])
            ->set('database', $database->id)
            ->set('storage', $storage->id)
            ->set('interval', '0 * * * *')
            ->set('keep', '10')
            ->call('create')
            ->assertSuccessful();

        Bus::assertDispatched(RunBackup::class);

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
            'provider' => \App\Enums\StorageProvider::DROPBOX
        ]);

        $backup = Backup::factory()->create([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $storage->id,
        ]);

        Livewire::test(DatabaseBackups::class, ['server' => $this->server])
            ->assertSee([
                $backup->database->name,
            ]);
    }

    public function test_delete_database(): void
    {
        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $storage = StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => \App\Enums\StorageProvider::DROPBOX
        ]);

        $backup = Backup::factory()->create([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $storage->id,
        ]);

        Livewire::test(DatabaseBackups::class, ['server' => $this->server])
            ->set('deleteId', $backup->id)
            ->call('delete');

        $this->assertDatabaseMissing('backups', [
            'id' => $backup->id,
        ]);
    }
}
