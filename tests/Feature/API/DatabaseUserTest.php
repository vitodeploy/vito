<?php

namespace Tests\Feature\API;

use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\DatabaseUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DatabaseUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database_user(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake();

        $this->json('POST', route('api.projects.servers.database-users.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'username' => 'user',
            'password' => 'password',
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'username' => 'user',
                'status' => DatabaseUserStatus::READY,
            ]);
    }

    public function test_create_database_user_with_remote(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake();

        $this->json('POST', route('api.projects.servers.database-users.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'username' => 'user',
            'password' => 'password',
            'host' => '%',
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'username' => 'user',
                'host' => '%',
                'status' => DatabaseUserStatus::READY,
            ]);
    }

    public function test_see_database_users_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('GET', route('api.projects.servers.database-users', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'username' => $databaseUser->username,
            ]);
    }

    public function test_show_database_user(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('GET', route('api.projects.servers.database-users.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'databaseUser' => $databaseUser,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'username' => $databaseUser->username,
            ]);
    }

    public function test_link_database(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake();

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('POST', route('api.projects.servers.database-users.link', [
            'project' => $this->server->project,
            'server' => $this->server,
            'databaseUser' => $databaseUser,
        ]), [
            'databases' => [$database->name],
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'username' => $databaseUser->username,
                'databases' => [$database->name],
            ]);
    }

    public function test_delete_database_user(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        SSH::fake();

        /** @var DatabaseUser $databaseUser */
        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('DELETE', route('api.projects.servers.database-users.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'databaseUser' => $databaseUser,
        ]))
            ->assertNoContent();
    }
}
