<?php

namespace Tests\Feature;

use App\Actions\HostedDomain\ActivateHostedDomain;
use App\Actions\Site\DisableSsl;
use App\Actions\Site\EnableSsl;
use App\Enums\HostedDomainStatus;
use App\Enums\SslMethod;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Http\Resources\ServerResource;
use App\Models\HostedDomain;
use App\Models\Metric;
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
