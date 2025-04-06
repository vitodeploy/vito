<?php

namespace Tests\Feature\API;

use App\Enums\DatabaseStatus;
use App\Enums\StorageProvider;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\StorageProvider as StorageProviderModel;
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
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
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

    public function test_clone_database(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake();

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'source_db',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $this->json('POST', route('api.projects.servers.databases.clone', [
            'project' => $this->server->project,
            'server' => $this->server,
            'database' => $database,
        ]), [
            'name' => 'cloned_db',
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'cloned_db',
                'status' => DatabaseStatus::READY,
            ]);

        $this->assertDatabaseHas('databases', [
            'server_id' => $this->server->id,
            'name' => 'cloned_db',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function test_clone_database_with_existing_name(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake();

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server->id,
        ]);

        // Create database with name that will conflict
        Database::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'existing_db',
        ]);

        $this->json('POST', route('api.projects.servers.databases.clone', [
            'project' => $this->server->project,
            'server' => $this->server,
            'database' => $database,
        ]), [
            'name' => 'existing_db',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create local storage provider
        $this->storageProvider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->server->project_id,
            'provider' => StorageProvider::LOCAL,
            'profile' => 'local-test',
            'credentials' => [
                'path' => '/home/vito/backups',
            ],
        ]);
    }
}
