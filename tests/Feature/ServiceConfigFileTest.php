<?php

use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('get config file', function () {
    $this->actingAs($this->user);

    $service = Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'mysql',
        'type' => 'database',
    ]);

    SSH::fake('config file content');

    $response = $this->get(route('services.config', [
        'server' => $this->server,
        'service' => $service->id,
        'config_name' => 'my.cnf',
    ]));

    $response->assertSuccessful()
        ->assertJson([
            'content' => 'config file content',
        ]);
});

test('get config file not found', function () {
    $this->actingAs($this->user);

    $service = Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'mysql',
        'type' => 'database',
    ]);

    $response = $this->get(route('services.config', [
        'server' => $this->server,
        'service' => $service->id,
        'config_name' => 'nonexistent.conf',
    ]));

    $response->assertSessionHasErrors(['config_name']);
});

test('update config file', function () {
    $this->actingAs($this->user);

    $service = Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'mysql',
        'type' => 'database',
    ]);

    SSH::fake('Active: active');

    $response = $this->patch(route('services.config.update', [
        'server' => $this->server,
        'service' => $service->id,
    ]), [
        'config_name' => 'my.cnf',
        'content' => 'new config content',
    ]);

    $response->assertSessionDoesntHaveErrors()
        ->assertRedirect();
});

test('update config file validates input', function () {
    $this->actingAs($this->user);

    $service = Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'mysql',
        'type' => 'database',
    ]);

    $response = $this->patch(route('services.config.update', [
        'server' => $this->server,
        'service' => $service->id,
    ]), [
        'config_name' => 'my.cnf',
    ]);

    $response->assertSessionHasErrors(['content']);
});

test('service without config paths returns error', function () {
    $this->actingAs($this->user);

    $service = Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'vito-agent',
        'type' => 'monitoring',
    ]);

    $response = $this->get(route('services.config', [
        'server' => $this->server,
        'service' => $service->id,
        'config_name' => 'test.conf',
    ]));

    $response->assertSessionHasErrors(['config_paths']);
});
