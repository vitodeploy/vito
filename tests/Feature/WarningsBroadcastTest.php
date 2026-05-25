<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class WarningsBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_resource_includes_warnings(): void
    {
        $this->server->update(['updates' => 5]);

        $resource = (new ServerResource($this->server->fresh()))->toArray(new Request);

        $this->assertArrayHasKey('warnings', $resource);
        $this->assertEquals('updates_available', $resource['warnings'][0]['key']);
        $this->assertEquals(5, $resource['warnings'][0]['count']);
    }

    public function test_enable_ssl_broadcasts_site_updated(): void
    {
        SSH::fake();
        Event::fake([SocketEvent::class]);

        $this->site->update(['ssl_enabled' => false]);

        app(EnableSsl::class)->enable($this->site);

        Event::assertDispatched(
            SocketEvent::class,
            fn (SocketEvent $event) => $event->data->type === 'site.updated'
                && ($event->data->data['id'] ?? null) === $this->site->id
        );
    }

    public function test_disable_ssl_broadcasts_site_updated(): void
    {
        SSH::fake();
        Event::fake([SocketEvent::class]);

        $this->site->update(['ssl_enabled' => true]);

        app(DisableSsl::class)->disable($this->site);

        Event::assertDispatched(
            SocketEvent::class,
            fn (SocketEvent $event) => $event->data->type === 'site.updated'
                && ($event->data->data['id'] ?? null) === $this->site->id
        );
    }

    public function test_activate_hosted_domain_broadcasts_site_updated(): void
    {
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
    }

    public function test_proxied_site_broadcast_carries_needs_first_deploy_warning(): void
    {
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
    }

    public function test_proxied_site_broadcast_omits_warning_after_finished_deployment(): void
    {
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
    }

    public function test_proxied_site_warns_when_worker_failed_with_error(): void
    {
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

        $this->assertNotNull($warning);
        $this->assertSame('10:10_00: ERROR (no such file)', $warning['error']);
    }

    public function test_proxied_site_does_not_warn_for_running_worker(): void
    {
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

        $this->assertNull(collect($proxiedSite->getWarnings())->firstWhere('key', 'worker_not_running'));
    }

    public function test_proxied_site_does_not_warn_for_transient_worker_status(): void
    {
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

        $this->assertNull(collect($proxiedSite->getWarnings())->firstWhere('key', 'worker_not_running'));
    }

    public function test_non_proxied_site_warns_when_any_worker_failed(): void
    {
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

        $this->assertNotNull($warning);
        $this->assertSame($worker->id, $warning['worker_id']);
        $this->assertSame('queue', $warning['name']);
        $this->assertSame('queue: ERROR (spawn error)', $warning['error']);
    }

    public function test_non_proxied_site_does_not_warn_for_intentionally_stopped_worker(): void
    {
        Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'name' => 'queue',
            'status' => WorkerStatus::STOPPED,
        ]);

        $this->site->load('workers');

        $this->assertNull(collect($this->site->getWarnings())->firstWhere('key', 'worker_not_running'));
    }

    public function test_metric_observer_broadcasts_on_reboot_required_transition(): void
    {
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
    }

    public function test_metric_observer_does_not_broadcast_when_reboot_required_unchanged(): void
    {
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
    }

    public function test_metric_observer_fires_when_metric_created_directly_on_relation(): void
    {
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
    }
}
