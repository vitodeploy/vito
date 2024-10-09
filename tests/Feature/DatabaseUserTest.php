<?php

namespace Tests\Feature;

use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\DatabaseUser;
use App\Web\Pages\Servers\Databases\Users;
use App\Web\Pages\Servers\Databases\Widgets\DatabaseUsersList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        Livewire::test(Users::class, [
            'server' => $this->server,
        ])
            ->callAction('create', [
                'username' => 'user',
                'password' => 'password',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'status' => DatabaseUserStatus::READY,
        ]);
    }

    public function test_create_database_user_with_remote(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        Livewire::test(Users::class, [
            'server' => $this->server,
        ])
            ->callAction('create', [
                'username' => 'user',
                'password' => 'password',
                'remote' => true,
                'host' => '%',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'host' => '%',
            'status' => DatabaseUserStatus::READY,
        ]);
    }

    public function test_see_database_users_list(): void
    {
        $this->actingAs($this->user);

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->get(
            Users::getUrl([
                'server' => $this->server,
            ])
        )
            ->assertSuccessful()
            ->assertSee($databaseUser->username);
    }

    public function test_delete_database_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        Livewire::test(DatabaseUsersList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('delete', $databaseUser->id)
            ->assertSuccessful();

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

        Livewire::test(DatabaseUsersList::class, [
            'server' => $this->server,
        ])
            ->callTableAction('link', $databaseUser->id, [
                'databases' => [],
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('database_users', [
            'username' => $databaseUser->username,
            'databases' => json_encode([]),
        ]);
    }
}
