<?php

use App\Enums\DatabaseStatus;
use App\Facades\SSH;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('create database', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    SSH::fake();

    $this->json('POST', route('api.projects.servers.databases.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'name' => 'database',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => 'database',
            'status' => DatabaseStatus::READY,
        ]);
});

test('show database', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Database $database */
    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('GET', route('api.projects.servers.databases.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'database' => $database,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => $database->name,
        ]);
});

test('see databases list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Database $database */
    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('GET', route('api.projects.servers.databases', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => $database->name,
        ]);
});

test('delete database', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    SSH::fake();

    /** @var Database $database */
    $database = Database::factory()->create([
        'server_id' => $this->server,
    ]);

    $this->json('DELETE', route('api.projects.servers.databases.delete', [
        'project' => $this->server->project,
        'server' => $this->server,
        'database' => $database,
    ]))
        ->assertSuccessful()
        ->assertNoContent();
});
