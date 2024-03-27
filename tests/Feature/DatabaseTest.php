<?php

namespace Tests\Feature;

use App\Enums\DatabaseStatus;
use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('servers.databases.store', $this->server), [
            'name' => 'database',
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

        $this->post(route('servers.databases.store', $this->server), [
            'name' => 'database',
            'user' => 'on',
            'username' => 'user',
            'password' => 'password',
            'remote' => 'on',
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

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->get(route('servers.databases', $this->server))
            ->assertSuccessful()
            ->assertSee($database->name);
    }

    public function test_delete_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->delete(route('servers.databases.destroy', [$this->server, $database]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('databases', [
            'id' => $database->id,
        ]);
    }
}
