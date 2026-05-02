<?php

namespace Tests\Feature;

use App\Enums\DatabaseStatus;
use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\DatabaseUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('databases.store', $this->server), [
            'name' => 'database',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('databases', [
            'name' => 'database',
            'status' => DatabaseStatus::READY,
        ]);
    }

    public function test_create_database_with_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
            'username' => 'user',
            'databases' => [],
            'status' => DatabaseUserStatus::READY,
        ]);

        $this->post(route('databases.store', $this->server), [
            'name' => 'database',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'user' => true,
            'existing_user_id' => $databaseUser->id,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('databases', [
            'name' => 'database',
            'status' => DatabaseStatus::READY,
        ]);

        $databaseUser->refresh();
        $this->assertContains('database', $databaseUser->databases);
    }

    public function test_create_database_with_existing_user(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        Database::factory()->create([
            'server_id' => $this->server,
            'name' => 'existing_db',
            'status' => DatabaseStatus::READY,
        ]);

        $databaseUser = DatabaseUser::factory()->create([
            'server_id' => $this->server,
            'username' => 'existing_user',
            'databases' => ['existing_db'],
            'status' => DatabaseUserStatus::READY,
        ]);

        $this->post(route('databases.store', $this->server), [
            'name' => 'new_database',
            'charset' => 'utf8mb3',
            'collation' => 'utf8mb3_general_ci',
            'user' => true,
            'existing_user_id' => $databaseUser->id,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('databases', [
            'name' => 'new_database',
            'status' => DatabaseStatus::READY,
        ]);

        $databaseUser->refresh();
        $this->assertContains('existing_db', $databaseUser->databases);
        $this->assertContains('new_database', $databaseUser->databases);
    }

    public function test_see_databases_list(): void
    {
        $this->actingAs($this->user);

        Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->get(route('databases', $this->server))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('databases/index'));
    }

    public function test_delete_database(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->delete(route('databases.destroy', [
            'server' => $this->server,
            'database' => $database,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertSoftDeleted('databases', [
            'id' => $database->id,
        ]);
    }

    public function test_sync_databases(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->patch(route('databases.sync', $this->server))
            ->assertSessionDoesntHaveErrors();
    }
}
