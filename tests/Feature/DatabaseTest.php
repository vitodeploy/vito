<?php

namespace Tests\Feature;

use App\Enums\DatabaseStatus;
use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Web\Pages\Servers\Databases\Index;
use App\Web\Pages\Servers\Databases\Widgets\DatabasesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        Livewire::test(Index::class, [
            'server' => $this->server,
        ])
            ->callAction('create', [
                'name' => 'database',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('databases', [
            'name' => 'database',
            'status' => DatabaseStatus::READY,
        ]);
    }

    public function test_create_database_with_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        Livewire::test(Index::class, [
            'server' => $this->server,
        ])
            ->callAction('create', [
                'name' => 'database',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'user' => true,
                'username' => 'user',
                'password' => 'password',
                'remote' => true,
                'host' => '%',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('databases', [
            'name' => 'database',
            'status' => DatabaseStatus::READY,
        ]);

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'databases' => json_encode(['database']),
            'host' => '%',
            'status' => DatabaseUserStatus::READY,
        ]);
    }

    public function test_see_databases_list(): void
    {
        $this->actingAs($this->user);

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->get(Index::getUrl(['server' => $this->server]))
            ->assertSuccessful()
            ->assertSee($database->name);
    }

    public function test_delete_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        Livewire::test(DatabasesList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('delete', $database->id)
            ->assertSuccessful();

        $this->assertSoftDeleted('databases', [
            'id' => $database->id,
        ]);
    }

    public function test_sync_databases(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        Livewire::test(Index::class, [
            'server' => $this->server,
        ])
            ->callAction('sync')
            ->assertSuccessful();
    }
}
