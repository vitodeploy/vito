<?php

use App\Actions\Service\Install;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Service\UpdateVitoAgentConfigJob;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('install vito agent', function () {
    SSH::fake('Active: active');
    Http::fake([
        'https://api.github.com/repos/vitodeploy/agent/tags' => Http::response([['name' => '0.1.0']]),
    ]);

    $this->server->monitoring()?->delete();

    app(Install::class)->install($this->server, [
        'type' => 'monitoring',
        'name' => 'vito-agent',
        'version' => 'latest',
    ]);

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'name' => 'vito-agent',
        'type' => 'monitoring',
        'version' => '0.1.0',
        'status' => ServiceStatus::READY,
    ]);
});

test('install vito agent failed', function () {
    $this->server->monitoring()?->delete();
    SSH::fake('Active: inactive');
    Http::fake([
        'https://api.github.com/repos/vitodeploy/agent/tags' => Http::response([]),
    ]);

    $service = app(Install::class)->install($this->server, [
        'type' => 'monitoring',
        'name' => 'vito-agent',
        'version' => 'latest',
    ]);

    $service->refresh();
    expect($service->status)->toEqual(ServiceStatus::INSTALLATION_FAILED);
});

test('install nginx', function () {
    $this->server->webserver()->delete();

    SSH::fake('Active: active');

    app(Install::class)->install($this->server, [
        'type' => 'webserver',
        'name' => 'nginx',
        'version' => 'latest',
    ]);

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'name' => 'nginx',
        'type' => 'webserver',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);
});

test('install caddy', function () {
    $this->server->webserver()->delete();

    SSH::fake('Active: active');

    app(Install::class)->install($this->server, [
        'type' => 'webserver',
        'name' => 'caddy',
        'version' => 'latest',
    ]);

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'name' => 'caddy',
        'type' => 'webserver',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);
});

test('install mysql', function () {
    $this->server->database()->delete();

    SSH::fake('Active: active');

    app(Install::class)->install($this->server, [
        'type' => 'database',
        'name' => 'mysql',
        'version' => '8.4',
    ]);

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'name' => 'mysql',
        'type' => 'database',
        'version' => '8.4',
        'status' => ServiceStatus::READY,
    ]);
});

test('install mysql failed', function () {
    $this->expectException(ValidationException::class);
    app(Install::class)->install($this->server, [
        'type' => 'database',
        'name' => 'mysql',
        'version' => '8.4',
    ]);
});

test('install supervisor', function () {
    $this->server->processManager()->delete();

    SSH::fake('Active: active');

    app(Install::class)->install($this->server, [
        'type' => 'process_manager',
        'name' => 'supervisor',
        'version' => 'latest',
    ]);

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'name' => 'supervisor',
        'type' => 'process_manager',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);
});

test('install redis', function () {
    $this->server->memoryDatabase()->delete();

    SSH::fake('Active: active');

    app(Install::class)->install($this->server, [
        'type' => 'memory_database',
        'name' => 'redis',
        'version' => 'latest',
    ]);

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'name' => 'redis',
        'type' => 'memory_database',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);
});

test('installing service dispatches agent config update', function () {
    SSH::fake('Active: active');
    Bus::fake([UpdateVitoAgentConfigJob::class]);

    Service::factory()->vitoAgent()->create(['server_id' => $this->server->id]);
    $this->server->memoryDatabase()->delete();

    app(Install::class)->install($this->server, [
        'type' => 'memory_database',
        'name' => 'redis',
        'version' => 'latest',
    ]);

    Bus::assertDispatched(UpdateVitoAgentConfigJob::class);
});

test('installing service without unit does not dispatch agent config update', function () {
    SSH::fake('Active: active');
    Bus::fake([UpdateVitoAgentConfigJob::class]);

    Service::factory()->vitoAgent()->create(['server_id' => $this->server->id]);
    $this->server->services()->where('name', 'nodejs')->delete();

    app(Install::class)->install($this->server, [
        'type' => 'nodejs',
        'name' => 'nodejs',
        'version' => '20',
    ]);

    Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
});

test('failed install does not dispatch agent config update', function () {
    SSH::fake('inactive');
    Bus::fake([UpdateVitoAgentConfigJob::class]);

    Service::factory()->vitoAgent()->create(['server_id' => $this->server->id]);
    $this->server->memoryDatabase()->delete();

    app(Install::class)->install($this->server, [
        'type' => 'memory_database',
        'name' => 'redis',
        'version' => 'latest',
    ]);

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'name' => 'redis',
        'status' => ServiceStatus::INSTALLATION_FAILED,
    ]);
    Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
});

test('installing service without vito agent does not dispatch agent config update', function () {
    SSH::fake('Active: active');
    Bus::fake([UpdateVitoAgentConfigJob::class]);

    $this->server->memoryDatabase()->delete();

    app(Install::class)->install($this->server, [
        'type' => 'memory_database',
        'name' => 'redis',
        'version' => 'latest',
    ]);

    Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
});

test('installing vito agent writes config with services', function () {
    SSH::fake('Active: active');
    Bus::fake([UpdateVitoAgentConfigJob::class]);
    Http::fake([
        'https://api.github.com/repos/vitodeploy/agent/tags' => Http::response([['name' => '0.1.0']]),
    ]);

    $this->server->monitoring()?->delete();

    app(Install::class)->install($this->server, [
        'type' => 'monitoring',
        'name' => 'vito-agent',
        'version' => 'latest',
    ]);

    $config = json_decode(SSH::getUploadedContent(), true);
    expect($config['url'])->not->toBeEmpty();
    expect($config['secret'])->not->toBeEmpty();
    $this->assertArrayNotHasKey('data_retention', $config);
    expect(array_column($config['services'], 'unit'))->toContain('nginx');

    Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
});
