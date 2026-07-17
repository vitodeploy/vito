<?php

namespace Tests\Feature\API;

use App\Enums\ServiceStatus;
use App\Events\ServiceStatusChanged;
use App\Events\SocketEvent;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AgentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function agentService(string $secret = 'test-secret'): Service
    {
        return Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'type_data' => [
                'url' => '',
                'secret' => $secret,
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_store_metrics_with_full_payload(): void
    {
        $service = $this->agentService();

        $payload = [
            'load' => 0.09,
            'disk_total' => '76571',
            'disk_free' => '70927',
            'disk_used' => '2471',
            'memory_total' => '7937236',
            'memory_free' => '7008596',
            'memory_used' => '928640',
            'cpu_cores' => 4,
            'cpu_physical_cores' => 4,
            'cpu_usage_percent' => 0.9900990099009901,
            'cpu_per_core_usage_percent' => [0, 0, 0, 0],
            'cpu_steal_percent' => 0,
            'swap_total' => '0',
            'swap_free' => '0',
            'swap_used' => '0',
            'swap_used_percent' => 0,
            'oom_kill_count' => 0,
            'uptime_seconds' => 26759.36,
            'reboot_required' => false,
        ];

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            $payload,
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'load' => 0.09,
            'cpu_cores' => 4,
            'cpu_physical_cores' => 4,
            'cpu_usage_percent' => 0.99,
            'cpu_steal_percent' => 0,
            'swap_total' => 0,
            'swap_used_percent' => 0,
            'oom_kill_count' => 0,
            'uptime_seconds' => 26759.36,
            'reboot_required' => false,
        ]);

        $metric = $this->server->metrics()->latest('id')->first();
        $this->assertNotNull($metric);
        $this->assertEquals([0.0, 0.0, 0.0, 0.0], $metric->cpu_per_core_usage_percent);
    }

    public function test_store_metrics_with_minimal_payload(): void
    {
        $service = $this->agentService();

        $payload = [
            'load' => 0.5,
            'memory_total' => '1000',
            'memory_used' => '500',
            'memory_free' => '500',
            'disk_total' => '100',
            'disk_used' => '50',
            'disk_free' => '50',
        ];

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            $payload,
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'load' => 0.5,
            'cpu_cores' => null,
            'cpu_usage_percent' => null,
            'swap_total' => null,
            'oom_kill_count' => null,
            'uptime_seconds' => null,
            'reboot_required' => null,
        ]);
    }

    public function test_invalid_secret_returns_401_without_validating_payload(): void
    {
        $service = $this->agentService('correct-secret');

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            ['anything' => 'goes'],
            ['secret' => 'wrong-secret']
        )->assertStatus(401);

        $this->assertDatabaseCount('metrics', 0);
    }

    public function test_service_without_secret_cannot_be_used_as_agent_endpoint(): void
    {
        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'remote-monitor',
            'type' => 'monitoring',
            'type_data' => [
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            ['load' => 0.5, 'memory_total' => 1, 'memory_used' => 1, 'memory_free' => 1, 'disk_total' => 1, 'disk_used' => 1, 'disk_free' => 1],
            ['secret' => '']
        )->assertStatus(401);

        $this->assertDatabaseCount('metrics', 0);
    }

    public function test_invalid_payload_returns_422(): void
    {
        $service = $this->agentService();

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            ['load' => 'not-a-number'],
            ['secret' => 'test-secret']
        )->assertStatus(422);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalPayload(): array
    {
        return [
            'load' => 0.5,
            'memory_total' => '1000',
            'memory_used' => '500',
            'memory_free' => '500',
            'disk_total' => '100',
            'disk_used' => '50',
            'disk_free' => '50',
        ];
    }

    public function test_store_metrics_without_services_key_changes_no_statuses(): void
    {
        Event::fake([ServiceStatusChanged::class]);

        $service = $this->agentService();

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            $this->minimalPayload(),
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('metrics', ['server_id' => $this->server->id, 'load' => 0.5]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_store_metrics_with_services_updates_statuses(): void
    {
        Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

        $service = $this->agentService();
        $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

        $this->reportServiceStatus($service, $nginx, 'inactive');
        $this->reportServiceStatus($service, $nginx, 'inactive');

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::STOPPED]);
        $this->assertDatabaseHas('metrics', ['server_id' => $this->server->id, 'load' => 0.5]);
        Event::assertDispatched(fn (ServiceStatusChanged $event): bool => $event->service->id === $nginx->id
            && $event->previousStatus === ServiceStatus::READY
            && $event->newStatus === ServiceStatus::STOPPED);
        Event::assertDispatched(SocketEvent::class);
    }

    public function test_single_inactive_report_does_not_change_status(): void
    {
        Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

        $service = $this->agentService();
        $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

        $this->reportServiceStatus($service, $nginx, 'inactive');

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_duplicate_service_entries_count_as_one_reading(): void
    {
        Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

        $service = $this->agentService();
        $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            array_merge($this->minimalPayload(), [
                'services' => [
                    ['id' => $nginx->id, 'status' => 'inactive'],
                    ['id' => $nginx->id, 'status' => 'inactive'],
                ],
            ]),
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_agent_reported_restart_flap_does_not_change_status(): void
    {
        Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

        $service = $this->agentService();
        $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

        $this->reportServiceStatus($service, $nginx, 'inactive');
        $this->reportServiceStatus($service, $nginx, 'active');
        $this->reportServiceStatus($service, $nginx, 'inactive');

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    private function reportServiceStatus(Service $service, Service $target, string $status): void
    {
        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            array_merge($this->minimalPayload(), [
                'services' => [['id' => $target->id, 'status' => $status]],
            ]),
            ['secret' => 'test-secret']
        )->assertSuccessful();
    }

    public function test_services_entry_for_other_server_is_ignored(): void
    {
        Event::fake([ServiceStatusChanged::class]);

        $service = $this->agentService();
        $otherServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);
        $otherService = Service::factory()->create([
            'server_id' => $otherServer->id,
            'name' => 'nginx',
            'type' => 'webserver',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            array_merge($this->minimalPayload(), [
                'services' => [['id' => $otherService->id, 'status' => 'inactive']],
            ]),
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('services', ['id' => $otherService->id, 'status' => ServiceStatus::READY]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_services_entry_for_transitional_service_is_ignored(): void
    {
        Event::fake([ServiceStatusChanged::class]);

        $service = $this->agentService();
        $mysql = $this->server->services()->where('name', 'mysql')->firstOrFail();
        $mysql->update(['status' => ServiceStatus::INSTALLING]);

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            array_merge($this->minimalPayload(), [
                'services' => [['id' => $mysql->id, 'status' => 'active']],
            ]),
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('services', ['id' => $mysql->id, 'status' => ServiceStatus::INSTALLING]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_services_entry_with_unknown_status_is_ignored(): void
    {
        Event::fake([ServiceStatusChanged::class]);

        $service = $this->agentService();
        $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            array_merge($this->minimalPayload(), [
                'services' => [['id' => $nginx->id, 'status' => 'activating']],
            ]),
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_services_entry_for_monitoring_service_is_ignored(): void
    {
        Event::fake([ServiceStatusChanged::class]);

        $service = $this->agentService();

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            array_merge($this->minimalPayload(), [
                'services' => [['id' => $service->id, 'status' => 'inactive']],
            ]),
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('services', ['id' => $service->id, 'status' => ServiceStatus::READY]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_services_must_be_an_array(): void
    {
        $service = $this->agentService();

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            array_merge($this->minimalPayload(), ['services' => 'nope']),
            ['secret' => 'test-secret']
        )->assertStatus(422);

        $this->assertDatabaseCount('metrics', 0);
    }
}
