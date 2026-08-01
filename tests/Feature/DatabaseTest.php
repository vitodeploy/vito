<?php

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

uses(RefreshDatabase::class);

test('create database', function () {
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
});

test('create database with user', function () {
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
    expect($databaseUser->databases)->toContain('database');
});

test('create database with existing user', function () {
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
    expect($databaseUser->databases)->toContain('existing_db');
    expect($databaseUser->databases)->toContain('new_database');
});

test('see databases list', function () {
    $this->actingAs($this->user);

    Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->get(route('databases', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('databases/index'));
});

test('delete database', function () {
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
});

test('delete database keeps linked user databases a list', function () {
    $this->actingAs($this->user);

    SSH::fake();

    foreach (['db_one', 'db_two', 'db_three'] as $name) {
        Database::factory()->create([
            'server_id' => $this->server->id,
            'name' => $name,
        ]);
    }

    /** @var DatabaseUser $databaseUser */
    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server->id,
        'databases' => ['db_one', 'db_two', 'db_three'],
    ]);

    /** @var Database $middle */
    $middle = Database::query()->where('name', 'db_two')->firstOrFail();

    $this->delete(route('databases.destroy', [
        'server' => $this->server,
        'database' => $middle,
    ]))->assertSessionDoesntHaveErrors();

    $databaseUser->refresh();

    expect($databaseUser->databases)->toBe(['db_one', 'db_three']);
    expect(json_encode($databaseUser->databases))->toBe('["db_one","db_three"]');

    $this->get(route('database-users', $this->server))
        ->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page): void {
            $rows = $page->toArray()['props']['databaseUsers']['data'];

            expect(json_encode($rows[0]['databases']))->toBe('["db_one","db_three"]');
        });
});

test('sync databases', function () {
    $this->actingAs($this->user);

    SSH::fake();

    $this->patch(route('databases.sync', $this->server))
        ->assertSessionDoesntHaveErrors();
});

test('create postgresql database with icu collation', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseTestUsePostgresql();

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
});

test('create database rejects malicious collation', function () {
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
});

test('postgresql create script applies collation locale', function () {
    $rendered = view('ssh.services.database.postgresql.create', [
        'name' => 'pg_database',
        'charset' => 'UTF8',
        'collation' => 'en-US-x-icu',
    ])->render();

    $this->assertStringContainsString('CREATE DATABASE', $rendered);
    $this->assertStringContainsString('LOCALE_PROVIDER', $rendered);
    $this->assertStringContainsString('\gexec', $rendered);
    $this->assertStringContainsString('en-US-x-icu', $rendered);
});

test('sync postgresql preserves icu collation', function () {
    $this->actingAs($this->user);

    vitoPestFeatureDatabaseTestUsePostgresql();

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
});

function vitoPestFeatureDatabaseTestUsePostgresql(): void
{
    test()->server->services()->where('type', Mysql::type())->delete();
    test()->server->services()->create([
        'type' => Postgresql::type(),
        'name' => Postgresql::id(),
        'version' => '15',
        'status' => ServiceStatus::READY,
    ]);
    test()->server->refresh();
}
