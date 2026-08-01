<?php

use App\Enums\DatabaseUserStatus;
use App\Facades\SSH;
use App\Models\Database;
use App\Models\DatabaseUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('create database user', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    SSH::fake();

    $this->json('POST', route('api.projects.servers.database-users.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'username' => 'user',
        'password' => 'password',
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'username' => 'user',
            'status' => DatabaseUserStatus::READY,
        ]);
});

test('create database user with remote', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    SSH::fake();

    $this->json('POST', route('api.projects.servers.database-users.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'username' => 'user',
        'password' => 'password',
        'host' => '%',
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'username' => 'user',
            'host' => '%',
            'status' => DatabaseUserStatus::READY,
        ]);
});

test('see database users list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var DatabaseUser $databaseUser */
    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('GET', route('api.projects.servers.database-users', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'username' => $databaseUser->username,
        ]);
});

test('show database user', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var DatabaseUser $databaseUser */
    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('GET', route('api.projects.servers.database-users.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'username' => $databaseUser->username,
        ]);
});

test('link database', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    SSH::fake();

    /** @var Database $database */
    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    /** @var DatabaseUser $databaseUser */
    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('POST', route('api.projects.servers.database-users.link', [
        'project' => $this->server->project,
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]), [
        'databases' => [$database->name],
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'username' => $databaseUser->username,
            'databases' => [$database->name],
        ]);
});

test('delete database user', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    SSH::fake();

    /** @var DatabaseUser $databaseUser */
    $databaseUser = DatabaseUser::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('DELETE', route('api.projects.servers.database-users.delete', [
        'project' => $this->server->project,
        'server' => $this->server,
        'databaseUser' => $databaseUser,
    ]))
        ->assertNoContent();
});
