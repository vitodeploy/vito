<?php

namespace Tests\Feature;

use App\Enums\DatabaseStatus;
use App\Enums\DatabaseUserStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\DatabaseUser;
use App\Services\Database\Mysql;
use App\Services\Database\Postgresql;
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

    public function test_create_postgresql_database_with_icu_collation(): void
    {
        $this->actingAs($this->user);

        $this->usePostgresql();

        SSH::fake();

        $this->post(route('databases.store', $this->server), [
            'name' => 'pg_database',
            'charset' => 'UTF8',
            'collation' => 'en-US-x-icu',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('databases', [
            'name' => 'pg_database',
            'collation' => 'en-US-x-icu',
            'status' => DatabaseStatus::READY,
        ]);
    }

    public function test_create_database_rejects_malicious_collation(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->post(route('databases.store', $this->server), [
            'name' => 'database',
            'charset' => 'utf8mb4',
            'collation' => "x'; DROP DATABASE postgres; --",
        ])->assertSessionHasErrors('collation');

        $this->assertDatabaseMissing('databases', [
            'name' => 'database',
        ]);
    }

    public function test_postgresql_create_script_applies_collation_locale(): void
    {
        $rendered = view('ssh.services.database.postgresql.create', [
            'name' => 'pg_database',
            'charset' => 'UTF8',
            'collation' => 'en-US-x-icu',
        ])->render();

        $this->assertStringContainsString('CREATE DATABASE', $rendered);
        $this->assertStringContainsString('LOCALE_PROVIDER', $rendered);
        $this->assertStringContainsString('\gexec', $rendered);
        $this->assertStringContainsString('en-US-x-icu', $rendered);
    }

    public function test_sync_postgresql_preserves_icu_collation(): void
    {
        $this->actingAs($this->user);

        $this->usePostgresql();

        Database::factory()->create([
            'server_id' => $this->server,
            'name' => 'pg_database',
            'charset' => 'UTF8',
            'collation' => 'af-NA-x-icu',
            'status' => DatabaseStatus::READY,
        ]);

        SSH::fake(<<<'EOD'
         database_name | charset | collation
        ---------------+---------+-------------
         pg_database   | UTF8    | af-NA-x-icu
        (1 row)
        EOD);

        $this->patch(route('databases.sync', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('databases', [
            'server_id' => $this->server->id,
            'name' => 'pg_database',
            'collation' => 'af-NA-x-icu',
        ]);
    }

    private function usePostgresql(): void
    {
        $this->server->services()->where('type', Mysql::type())->delete();
        $this->server->services()->create([
            'type' => Postgresql::type(),
            'name' => Postgresql::id(),
            'version' => '15',
            'status' => ServiceStatus::READY,
        ]);
        $this->server->refresh();
    }
}
