<?php

namespace Tests\Feature;

use App\Enums\DatabaseStatus;
use App\Facades\SSH;
use App\Http\Livewire\Databases\DatabaseList;
use App\Jobs\Database\CreateOnServer;
use App\Jobs\Database\DeleteFromServer;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database(): void
    {
        $this->actingAs($this->user);

        Bus::fake();

        SSH::fake()->outputShouldBe('test');

        Livewire::test(DatabaseList::class, ['server' => $this->server])
            ->set('name', 'database')
            ->call('create')
            ->assertSuccessful();

        Bus::assertDispatched(CreateOnServer::class);

        $this->assertDatabaseHas('databases', [
            'name' => 'database',
            'status' => DatabaseStatus::CREATING,
        ]);
    }

    public function test_see_databases_list(): void
    {
        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        Livewire::test(DatabaseList::class, ['server' => $this->server])
            ->assertSee([
                $database->name,
            ]);
    }

    public function test_delete_database(): void
    {
        $this->actingAs($this->user);

        Bus::fake();

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        Livewire::test(DatabaseList::class, ['server' => $this->server])
            ->set('deleteId', $database->id)
            ->call('delete');

        $this->assertDatabaseHas('databases', [
            'id' => $database->id,
            'status' => DatabaseStatus::DELETING,
        ]);

        Bus::assertDispatched(DeleteFromServer::class);
    }
}
