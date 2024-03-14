<?php

namespace Tests\Feature;

use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\DatabaseUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('servers.databases.users.store', $this->server), [
            'username' => 'user',
            'password' => 'password',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'status' => DatabaseUserStatus::READY,
        ]);
    }

    public function test_see_database_users_list(): void
    {
        $this->actingAs($this->user);

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->get(route('servers.databases', $this->server))
            ->assertSee($databaseUser->username);
    }

    public function test_delete_database_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->delete(route('servers.databases.users.destroy', [$this->server, $databaseUser]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('database_users', [
            'id' => $databaseUser->id,
        ]);
    }
}
