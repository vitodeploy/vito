<?php

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('services list redacts type data secret', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'vito-agent',
        'type' => 'monitoring',
        'type_data' => [
            'url' => 'https://vito.test/agent-endpoint',
            'secret' => 'agent-secret',
            'data_retention' => 7,
        ],
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    $this->json('GET', route('api.projects.servers.services', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment(['url' => 'https://vito.test/agent-endpoint'])
        ->assertJsonMissing(['secret' => 'agent-secret']);
});

test('see services list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('GET', route('api.projects.servers.services', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => 'mysql',
        ])
        ->assertJsonFragment([
            'name' => 'nginx',
        ])
        ->assertJsonFragment([
            'name' => 'php',
        ])
        ->assertJsonFragment([
            'name' => 'supervisor',
        ])
        ->assertJsonFragment([
            'name' => 'redis',
        ])
        ->assertJsonFragment([
            'name' => 'ufw',
        ]);
});

test('show service', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);
    $service = $this->server->services()->firstOrFail();

    $this->json('GET', route('api.projects.servers.services.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'service' => $service,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'name' => $service->name,
        ]);
});

test('manage service', function (string $action) {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $service = $this->server->services()->firstOrFail();
    $service->status = ServiceStatus::STOPPED;
    $service->save();

    SSH::fake('Active: active');

    $this->json('POST', route('api.projects.servers.services.'.$action, [
        'project' => $this->server->project,
        'server' => $this->server,
        'service' => $service,
    ]))
        ->assertSuccessful()
        ->assertNoContent();
})->with('data');

test('uninstall service', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $service = $this->server->services()->where('type', 'process_manager')->firstOrFail();

    SSH::fake();

    $this->json('DELETE', route('api.projects.servers.services.uninstall', [
        'project' => $this->server->project,
        'server' => $this->server,
        'service' => $service,
    ]))
        ->assertSuccessful()
        ->assertNoContent();

    $this->assertDatabaseMissing('services', [
        'id' => $service->id,
    ]);
});

test('cannot uninstall service because it is being used', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $service = $this->server->services()->where('type', 'webserver')->firstOrFail();

    SSH::fake();

    $this->json('DELETE', route('api.projects.servers.services.uninstall', [
        'project' => $this->server->project,
        'server' => $this->server,
        'service' => $service,
    ]))
        ->assertJsonValidationErrorFor('service');
});

/**
 * @return array<array<string>>
 */
dataset('data', function () {
    return [
        ['start'],
        ['stop'],
        ['restart'],
        ['enable'],
        ['disable'],
    ];
});
