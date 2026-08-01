<?php

use App\Enums\OperatingSystem;
use App\Facades\SSH;
use App\ServerProviders\Custom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('get servers', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('GET', route('api.projects.servers', [
        'project' => $this->user->current_project_id,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => $this->server->name,
        ]);
});

test('get server', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('GET', route('api.projects.servers.show', [
        'project' => $this->user->current_project_id,
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => $this->server->name,
        ]);
});

test('create server', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    SSH::fake('user_not_found');

    // fake output for vito user check and service installations
    $this->json('POST', route('api.projects.servers.create', [
        'project' => $this->user->current_project_id,
    ]), [
        'provider' => Custom::id(),
        'name' => 'test',
        'ip' => '1.1.1.1',
        'port' => '22',
        'os' => OperatingSystem::UBUNTU22->value,
        'services' => [],
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => 'test',
        ]);
});

test('delete server', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    SSH::fake();

    $this->json('DELETE', route('api.projects.servers.delete', [
        'project' => $this->server->project_id,
        'server' => $this->server->id,
    ]))
        ->assertNoContent();
});

test('reboot server', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.reboot', [
        'project' => $this->server->project_id,
        'server' => $this->server->id,
    ]))
        ->assertNoContent();
});

test('upgrade server', function () {
    SSH::fake('Available updates:0');

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.upgrade', [
        'project' => $this->server->project_id,
        'server' => $this->server->id,
    ]))
        ->assertNoContent();
});
