<?php

namespace Tests\Feature;

use App\Enums\DatabaseStatus;
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

        SSH::fake()->outputShouldBe('test');

        $this->post(route('servers.databases.store', $this->server), [
            'name' => 'database',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('databases', [
            'name' => 'database',
            'status' => DatabaseStatus::READY,
        ]);
    }

    public function test_see_databases_list(): void
    {
        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->get(route('servers.databases', $this->server))
            ->assertSee($database->name);
    }

    public function test_delete_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake()->outputShouldBe('test');

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->delete(route('servers.databases.destroy', [$this->server, $database]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('databases', [
            'id' => $database->id,
        ]);
    }
}
