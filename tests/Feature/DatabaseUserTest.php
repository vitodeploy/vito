<?php

namespace Tests\Feature;

use App\Enums\DatabaseUserPermission;
use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\Database;
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
            'databases' => $this->castAsJson([]),
        ]);
    }

    public function test_update_database_user_password(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
            'password' => 'old_password',
        ]);

        $this->put(route('database-users.update', [
            'server' => $this->server,
            'databaseUser' => $databaseUser,
        ]), [
            'password' => 'new_password',
            'permission' => $databaseUser->permission->value,
        ])->assertSessionDoesntHaveErrors();

        $databaseUser->refresh();

        $this->assertEquals('new_password', $databaseUser->password);
    }

    public function test_update_database_user_host(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
            'host' => 'localhost',
        ]);

        $this->put(route('database-users.update', [
            'server' => $this->server,
            'databaseUser' => $databaseUser,
        ]), [
            'remote' => true,
            'host' => '%',
            'permission' => $databaseUser->permission->value,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('database_users', [
            'id' => $databaseUser->id,
            'host' => '%',
        ]);
    }

    public function test_update_database_user_password_and_host(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
            'password' => 'old_password',
            'host' => 'localhost',
        ]);

        $this->put(route('database-users.update', [
            'server' => $this->server,
            'databaseUser' => $databaseUser,
        ]), [
            'password' => 'new_password',
            'remote' => true,
            'host' => '192.168.1.1',
            'permission' => $databaseUser->permission->value,
        ])->assertSessionDoesntHaveErrors();

        $databaseUser->refresh();

        $this->assertEquals('new_password', $databaseUser->password);
        $this->assertEquals('192.168.1.1', $databaseUser->host);
    }

    public function test_create_database_user_with_admin_permission(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('database-users.store', [
            'server' => $this->server,
        ]), [
            'username' => 'user',
            'password' => 'password',
            'permission' => 'admin',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'permission' => 'admin',
            'status' => DatabaseUserStatus::READY,
        ]);
    }

    public function test_create_database_user_with_write_permission(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('database-users.store', [
            'server' => $this->server,
        ]), [
            'username' => 'user',
            'password' => 'password',
            'permission' => 'write',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'permission' => 'write',
            'status' => DatabaseUserStatus::READY,
        ]);
    }

    public function test_create_database_user_with_read_permission(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('database-users.store', [
            'server' => $this->server,
        ]), [
            'username' => 'user',
            'password' => 'password',
            'permission' => 'read',
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'permission' => 'read',
            'status' => DatabaseUserStatus::READY,
        ]);
    }

    public function test_update_database_user_permission(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $database = Database::factory()->create([
            'server_id' => $this->server,
            'name' => 'test_db',
        ]);

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
            'permission' => 'admin',
            'databases' => ['test_db'],
        ]);

        $this->put(route('database-users.update', [
            'server' => $this->server,
            'databaseUser' => $databaseUser,
        ]), [
            'permission' => 'read',
        ])->assertSessionDoesntHaveErrors();

        $databaseUser->refresh();

        $this->assertEquals(DatabaseUserPermission::READ, $databaseUser->permission);
    }
}
