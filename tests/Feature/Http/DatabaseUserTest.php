<?php

namespace Tests\Feature\Http;

use App\Enums\DatabaseUserStatus;
use App\Http\Livewire\Databases\DatabaseUserList;
use App\Jobs\DatabaseUser\CreateOnServer;
use App\Jobs\DatabaseUser\DeleteFromServer;
use App\Models\DatabaseUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database_user(): void
    {
        $this->actingAs($this->user);

        Bus::fake();

        Livewire::test(DatabaseUserList::class, ['server' => $this->server])
            ->set('username', 'user')
            ->set('password', 'password')
            ->call('create')
            ->assertSuccessful();

        Bus::assertDispatched(CreateOnServer::class);

        $this->assertDatabaseHas('database_users', [
            'username' => 'user',
            'status' => DatabaseUserStatus::CREATING,
        ]);
    }

    public function test_see_database_users_list(): void
    {
        $this->actingAs($this->user);

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        Livewire::test(DatabaseUserList::class, ['server' => $this->server])
            ->assertSee([
                $databaseUser->username,
            ]);
    }

    public function test_delete_database_user(): void
    {
        $this->actingAs($this->user);

        Bus::fake();

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
        ]);

        Livewire::test(DatabaseUserList::class, ['server' => $this->server])
            ->set('deleteId', $databaseUser->id)
            ->call('delete');

        $this->assertDatabaseHas('database_users', [
            'id' => $databaseUser->id,
            'status' => DatabaseUserStatus::DELETING,
        ]);

        Bus::assertDispatched(DeleteFromServer::class);
    }
}
