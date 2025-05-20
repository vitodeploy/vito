<?php

namespace Tests\Feature;

use App\Enums\DatabaseStatus;
use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('databases.store', $this->server), [
            'name' => 'database',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('databases', [
            'name' => 'database',
            'status' => DatabaseStatus::READY,
        ]);
    }

    public function test_create_database_with_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('databases.store', $this->server), [
            'name' => 'database',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'user' => true,
            'username' => 'user',
            'password' => 'password',
            'remote' => true,
            'host' => '%',
        ])->assertSessionDoesntHaveErrors();

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

        Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->get(route('databases', $this->server))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('databases/index'));
    }

    public function test_delete_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->delete(route('databases.destroy', [
            'server' => $this->server,
            'database' => $database,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertSoftDeleted('databases', [
            'id' => $database->id,
        ]);
    }

    public function test_sync_databases(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->patch(route('databases.sync', $this->server))
            ->assertSessionDoesntHaveErrors();
    }
}
