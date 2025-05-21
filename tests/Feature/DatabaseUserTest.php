<?php

namespace Tests\Feature;

use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\DatabaseUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DatabaseUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('database-users.store', [
            'server' => $this->server,
        ]), [
            'username' => 'user',
            'password' => 'password',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'status' => DatabaseUserStatus::READY,
        ]);
    }

    public function test_create_database_user_with_remote(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('database-users.store', [
            'server' => $this->server,
        ]), [
            'username' => 'user',
            'password' => 'password',
            'remote' => true,
            'host' => '%',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'host' => '%',
            'status' => DatabaseUserStatus::READY,
        ]);
    }

    public function test_see_database_users_list(): void
    {
        $this->actingAs($this->user);

        DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->get(route('database-users', $this->server))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('database-users/index'));
    }

    public function test_delete_database_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->delete(route('database-users.destroy', [
            'server' => $this->server,
            'databaseUser' => $databaseUser,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('database_users', [
            'id' => $databaseUser->id,
        ]);
    }

    public function test_unlink_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->put(route('database-users.link', [
            'server' => $this->server,
            'databaseUser' => $databaseUser,
        ]), [
            'databases' => [],
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('database_users', [
            'username' => $databaseUser->username,
            'databases' => json_encode([]),
        ]);
    }
}
