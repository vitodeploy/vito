<?php

use App\Actions\HostedDomain\ActivateHostedDomain;
use App\Actions\Site\DisableSsl;
use App\Actions\Site\EnableSsl;
use App\Actions\Site\UpdatePort;
use App\Enums\DeploymentStatus;
use App\Enums\HostedDomainStatus;
use App\Enums\SslMethod;
use App\Enums\WorkerStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Http\Resources\ServerResource;
use App\Models\Deployment;
use App\Models\HostedDomain;
use App\Models\Metric;
use App\Models\Site;
use App\Models\Worker;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('server resource includes warnings', function () {
    $this->server->update(['updates' => 5]);

    $resource = (new ServerResource($this->server->fresh()))->toArray(new Request);

    expect($resource)->toHaveKey('warnings');
    expect($resource['warnings'][0]['key'])->toEqual('updates_available');
    expect($resource['warnings'][0]['count'])->toEqual(5);
});

test('enable ssl broadcasts site updated', function () {
    SSH::fake();
    Event::fake([SocketEvent::class]);

    $this->site->update(['ssl_enabled' => false]);

    app(EnableSsl::class)->enable($this->site);

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'site.updated'
            && ($event->data->data['id'] ?? null) === $this->site->id
    );
});

test('disable ssl broadcasts site updated', function () {
    SSH::fake();
    Event::fake([SocketEvent::class]);

    $this->site->update(['ssl_enabled' => true]);

    app(DisableSsl::class)->disable($this->site);

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'site.updated'
            && ($event->data->data['id'] ?? null) === $this->site->id
    );
});

test('activate hosted domain broadcasts site updated', function () {
    SSH::fake();
    Event::fake([SocketEvent::class]);

    $hostedDomain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'example.com',
        'status' => HostedDomainStatus::PENDING,
        'ssl_method' => SslMethod::NONE,
    ]);

    app(ActivateHostedDomain::class)->activate($hostedDomain);

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'site.updated'
            && ($event->data->data['id'] ?? null) === $this->site->id
    );
});

test('proxied site broadcast carries needs first deploy warning', function () {
    SSH::fake();

    /** @var Site $proxiedSite */
    $proxiedSite = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-foo',
        'type' => NodeSite::id(),
        'port' => 3000,
        'type_data' => ['node_version' => '22', 'package_manager' => 'npm'],
    ]);

    Event::fake([SocketEvent::class]);

    app(UpdatePort::class)->update($proxiedSite, ['port' => 4000]);

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'site.updated'
            && ($event->data->data['id'] ?? null) === $proxiedSite->id
            && collect($event->data->data['warnings'] ?? [])->contains(fn ($w) => $w['key'] === 'needs_first_deploy'),
    );
});

test('proxied site broadcast omits warning after finished deployment', function () {
    SSH::fake();

    /** @var Site $proxiedSite */
    $proxiedSite = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-foo',
        'type' => NodeSite::id(),
        'port' => 3000,
        'type_data' => ['node_version' => '22', 'package_manager' => 'npm'],
    ]);

    Deployment::factory()->create([
        'site_id' => $proxiedSite->id,
        'status' => DeploymentStatus::FINISHED,
    ]);

    Event::fake([SocketEvent::class]);

    app(UpdatePort::class)->update($proxiedSite, ['port' => 4000]);

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'site.updated'
            && ($event->data->data['id'] ?? null) === $proxiedSite->id
            && ! collect($event->data->data['warnings'] ?? [])->contains(fn ($w) => $w['key'] === 'needs_first_deploy'),
    );
});

test('proxied site warns when worker failed with error', function () {
    /** @var Site $proxiedSite */
    $proxiedSite = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-foo',
        'type' => NodeSite::id(),
        'port' => 3000,
        'type_data' => ['node_version' => '22', 'package_manager' => 'npm'],
    ]);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $proxiedSite->id,
        'name' => 'app',
        'status' => WorkerStatus::FAILED,
        'error' => '10:10_00: ERROR (no such file)',
    ]);

    $proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);
    $proxiedSite->load('workers');

    $warning = collect($proxiedSite->getWarnings())->firstWhere('key', 'worker_not_running');

    expect($warning)->not->toBeNull();
    expect($warning['error'])->toBe('10:10_00: ERROR (no such file)');
});

test('proxied site does not warn for running worker', function () {
    /** @var Site $proxiedSite */
    $proxiedSite = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-foo',
        'type' => NodeSite::id(),
        'port' => 3000,
        'type_data' => ['node_version' => '22', 'package_manager' => 'npm'],
    ]);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $proxiedSite->id,
        'name' => 'app',
        'status' => WorkerStatus::RUNNING,
    ]);

    $proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);
    $proxiedSite->load('workers');

    expect(collect($proxiedSite->getWarnings())->firstWhere('key', 'worker_not_running'))->toBeNull();
});

test('proxied site does not warn for transient worker status', function () {
    /** @var Site $proxiedSite */
    $proxiedSite = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'isolated-foo',
        'type' => NodeSite::id(),
        'port' => 3000,
        'type_data' => ['node_version' => '22', 'package_manager' => 'npm'],
    ]);

    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $proxiedSite->id,
        'name' => 'app',
        'status' => WorkerStatus::RESTARTING,
    ]);

    $proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);
    $proxiedSite->load('workers');

    expect(collect($proxiedSite->getWarnings())->firstWhere('key', 'worker_not_running'))->toBeNull();
});

test('non proxied site warns when any worker failed', function () {
    /** @var Worker $worker */
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'name' => 'queue',
        'status' => WorkerStatus::FAILED,
        'error' => 'queue: ERROR (spawn error)',
    ]);

    $this->site->load('workers');

    $warning = collect($this->site->getWarnings())->firstWhere('key', 'worker_not_running');

    expect($warning)->not->toBeNull();
    expect($warning['worker_id'])->toBe($worker->id);
    expect($warning['name'])->toBe('queue');
    expect($warning['error'])->toBe('queue: ERROR (spawn error)');
});

test('non proxied site does not warn for intentionally stopped worker', function () {
    Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'name' => 'queue',
        'status' => WorkerStatus::STOPPED,
    ]);

    $this->site->load('workers');

    expect(collect($this->site->getWarnings())->firstWhere('key', 'worker_not_running'))->toBeNull();
});

test('metric observer broadcasts on reboot required transition', function () {
    Metric::factory()->create([
        'server_id' => $this->server->id,
        'reboot_required' => false,
    ]);

    Event::fake([SocketEvent::class]);

    Metric::factory()->create([
        'server_id' => $this->server->id,
        'reboot_required' => true,
    ]);

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'server.updated'
            && ($event->data->data['id'] ?? null) === $this->server->id
    );
});

test('metric observer does not broadcast when reboot required unchanged', function () {
    Metric::factory()->create([
        'server_id' => $this->server->id,
        'reboot_required' => false,
    ]);

    Event::fake([SocketEvent::class]);

    Metric::factory()->create([
        'server_id' => $this->server->id,
        'reboot_required' => false,
    ]);

    Event::assertNotDispatched(SocketEvent::class);
});

test('metric observer fires when metric created directly on relation', function () {
    Metric::factory()->create([
        'server_id' => $this->server->id,
        'reboot_required' => false,
    ]);

    Event::fake([SocketEvent::class]);

    $this->server->metrics()->create([
        'load' => 0.1,
        'memory_total' => 1000,
        'memory_used' => 100,
        'memory_free' => 900,
        'disk_total' => 1000,
        'disk_used' => 100,
        'disk_free' => 900,
        'reboot_required' => true,
    ]);

    Event::assertDispatched(
        SocketEvent::class,
        fn (SocketEvent $event) => $event->data->type === 'server.updated'
            && ($event->data->data['id'] ?? null) === $this->server->id
    );
});
