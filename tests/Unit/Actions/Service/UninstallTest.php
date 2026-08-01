<?php

use App\Actions\Network\CreateNetwork;
use App\Actions\Service\Uninstall;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Service\UpdateVitoAgentConfigJob;
use App\Models\Database;
use App\Models\Service;
use App\Models\Worker;
use App\Services\Webserver\Caddy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('uninstall vito agent', function () {
    SSH::fake();

    $this->server->monitoring()?->delete();

    $service = Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'vito-agent',
        'type' => 'monitoring',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    app(Uninstall::class)->uninstall($this->server->monitoring());

    $this->assertDatabaseMissing('services', [
        'id' => $service->id,
    ]);
});

test('uninstalling service dispatches agent config update', function () {
    SSH::fake();
    Bus::fake([UpdateVitoAgentConfigJob::class]);

    Service::factory()->vitoAgent()->create(['server_id' => $this->server->id]);

    $redis = $this->server->services()->where('name', 'redis')->firstOrFail();

    app(Uninstall::class)->uninstall($redis);

    $this->assertDatabaseMissing('services', ['id' => $redis->id]);
    Bus::assertDispatched(UpdateVitoAgentConfigJob::class);
});

test('uninstalling vito agent does not dispatch agent config update', function () {
    SSH::fake();
    Bus::fake([UpdateVitoAgentConfigJob::class]);

    $this->server->monitoring()?->delete();

    Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'vito-agent',
        'type' => 'monitoring',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    app(Uninstall::class)->uninstall($this->server->monitoring());

    Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
});

test('cannot uninstall nginx', function () {
    SSH::fake();

    $this->expectException(ValidationException::class);

    app(Uninstall::class)->uninstall($this->server->webserver());
});

test('cannot uninstall caddy', function () {
    SSH::fake();

    $caddy = $this->server->webserver();
    $caddy->update(['name' => Caddy::id()]);

    $this->expectException(ValidationException::class);

    app(Uninstall::class)->uninstall($caddy);
});

test('cannot uninstall mysql', function () {
    SSH::fake();

    Database::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->expectException(ValidationException::class);

    app(Uninstall::class)->uninstall($this->server->database());
});

test('cannot uninstall wireguard when server is network member', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $this->expectException(ValidationException::class);

    app(Uninstall::class)->uninstall($this->server->service('vpn'));
});

test('can uninstall wireguard when server is not a network member', function () {
    SSH::fake();

    $service = Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'wireguard',
        'type' => 'vpn',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    app(Uninstall::class)->uninstall($service);

    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});

test('cannot uninstall supervisor', function () {
    SSH::fake();

    Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);

    $this->expectException(ValidationException::class);

    app(Uninstall::class)->uninstall($this->server->processManager());
});
