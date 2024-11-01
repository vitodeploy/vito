<?php

namespace Tests\Feature\API;

use App\Enums\DatabaseStatus;
use App\Facades\SSH;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake();

        $this->json('POST', route('api.projects.servers.databases.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'name' => 'database',
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'database',
                'status' => DatabaseStatus::READY,
            ]);
    }

    public function test_show_database(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('GET', route('api.projects.servers.databases.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'database' => $database,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => $database->name,
            ]);
    }

    public function test_see_databases_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('GET', route('api.projects.servers.databases', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => $database->name,
            ]);
    }

    public function test_delete_database(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake();

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('DELETE', route('api.projects.servers.databases.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'database' => $database,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }
}
