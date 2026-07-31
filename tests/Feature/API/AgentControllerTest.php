<?php

use App\Enums\ServiceStatus;
use App\Events\ServiceStatusChanged;
use App\Events\SocketEvent;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function vitoPestFeatureAPIAgentControllerTestAgentService(string $secret = 'test-secret'): Service
{
    return Service::factory()->create([
        'server_id' => test()->server->id,
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

test('store metrics with full payload', function () {
    $service = vitoPestFeatureAPIAgentControllerTestAgentService();

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
    expect($metric)->not->toBeNull();
    expect($metric->cpu_per_core_usage_percent)->toEqual([0.0, 0.0, 0.0, 0.0]);
});

test('store metrics with minimal payload', function () {
    $service = vitoPestFeatureAPIAgentControllerTestAgentService();

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
});

test('invalid secret returns 401 without validating payload', function () {
    $service = vitoPestFeatureAPIAgentControllerTestAgentService('correct-secret');

    $this->json(
        'POST',
        route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
        ['anything' => 'goes'],
        ['secret' => 'wrong-secret']
    )->assertStatus(401);

    $this->assertDatabaseCount('metrics', 0);
});

test('service without secret cannot be used as agent endpoint', function () {
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
});

test('invalid payload returns 422', function () {
    $service = vitoPestFeatureAPIAgentControllerTestAgentService();

    $this->json(
        'POST',
        route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
        ['load' => 'not-a-number'],
        ['secret' => 'test-secret']
    )->assertStatus(422);
});

/**
 * @return array<string, mixed>
 */
function vitoPestFeatureAPIAgentControllerTestMinimalPayload(): array
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

test('store metrics without services key changes no statuses', function () {
    Event::fake([ServiceStatusChanged::class]);

    $service = vitoPestFeatureAPIAgentControllerTestAgentService();

    $this->json(
        'POST',
        route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
        vitoPestFeatureAPIAgentControllerTestMinimalPayload(),
        ['secret' => 'test-secret']
    )->assertSuccessful();

    $this->assertDatabaseHas('metrics', ['server_id' => $this->server->id, 'load' => 0.5]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('store metrics with services updates statuses', function () {
    Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

    $service = vitoPestFeatureAPIAgentControllerTestAgentService();
    $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

    vitoPestFeatureAPIAgentControllerTestReportServiceStatus($service, $nginx, 'inactive');
    vitoPestFeatureAPIAgentControllerTestReportServiceStatus($service, $nginx, 'inactive');

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::STOPPED]);
    $this->assertDatabaseHas('metrics', ['server_id' => $this->server->id, 'load' => 0.5]);
    Event::assertDispatched(fn (ServiceStatusChanged $event): bool => $event->service->id === $nginx->id
        && $event->previousStatus === ServiceStatus::READY
        && $event->newStatus === ServiceStatus::STOPPED);
    Event::assertDispatched(SocketEvent::class);
});

test('single inactive report does not change status', function () {
    Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

    $service = vitoPestFeatureAPIAgentControllerTestAgentService();
    $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

    vitoPestFeatureAPIAgentControllerTestReportServiceStatus($service, $nginx, 'inactive');

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('duplicate service entries count as one reading', function () {
    Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

    $service = vitoPestFeatureAPIAgentControllerTestAgentService();
    $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

    $this->json(
        'POST',
        route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
        array_merge(vitoPestFeatureAPIAgentControllerTestMinimalPayload(), [
            'services' => [
                ['id' => $nginx->id, 'status' => 'inactive'],
                ['id' => $nginx->id, 'status' => 'inactive'],
            ],
        ]),
        ['secret' => 'test-secret']
    )->assertSuccessful();

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('agent reported restart flap does not change status', function () {
    Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

    $service = vitoPestFeatureAPIAgentControllerTestAgentService();
    $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

    vitoPestFeatureAPIAgentControllerTestReportServiceStatus($service, $nginx, 'inactive');
    vitoPestFeatureAPIAgentControllerTestReportServiceStatus($service, $nginx, 'active');
    vitoPestFeatureAPIAgentControllerTestReportServiceStatus($service, $nginx, 'inactive');

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

function vitoPestFeatureAPIAgentControllerTestReportServiceStatus(Service $service, Service $target, string $status): void
{
    test()->json(
        'POST',
        route('api.servers.agent', ['server' => test()->server, 'id' => $service->id]),
        array_merge(vitoPestFeatureAPIAgentControllerTestMinimalPayload(), [
            'services' => [['id' => $target->id, 'status' => $status]],
        ]),
        ['secret' => 'test-secret']
    )->assertSuccessful();
}

test('services entry for other server is ignored', function () {
    Event::fake([ServiceStatusChanged::class]);

    $service = vitoPestFeatureAPIAgentControllerTestAgentService();
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
        array_merge(vitoPestFeatureAPIAgentControllerTestMinimalPayload(), [
            'services' => [['id' => $otherService->id, 'status' => 'inactive']],
        ]),
        ['secret' => 'test-secret']
    )->assertSuccessful();

    $this->assertDatabaseHas('services', ['id' => $otherService->id, 'status' => ServiceStatus::READY]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('services entry for transitional service is ignored', function () {
    Event::fake([ServiceStatusChanged::class]);

    $service = vitoPestFeatureAPIAgentControllerTestAgentService();
    $mysql = $this->server->services()->where('name', 'mysql')->firstOrFail();
    $mysql->update(['status' => ServiceStatus::INSTALLING]);

    $this->json(
        'POST',
        route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
        array_merge(vitoPestFeatureAPIAgentControllerTestMinimalPayload(), [
            'services' => [['id' => $mysql->id, 'status' => 'active']],
        ]),
        ['secret' => 'test-secret']
    )->assertSuccessful();

    $this->assertDatabaseHas('services', ['id' => $mysql->id, 'status' => ServiceStatus::INSTALLING]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('services entry with unknown status is ignored', function () {
    Event::fake([ServiceStatusChanged::class]);

    $service = vitoPestFeatureAPIAgentControllerTestAgentService();
    $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

    $this->json(
        'POST',
        route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
        array_merge(vitoPestFeatureAPIAgentControllerTestMinimalPayload(), [
            'services' => [['id' => $nginx->id, 'status' => 'activating']],
        ]),
        ['secret' => 'test-secret']
    )->assertSuccessful();

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('services entry for monitoring service is ignored', function () {
    Event::fake([ServiceStatusChanged::class]);

    $service = vitoPestFeatureAPIAgentControllerTestAgentService();

    $this->json(
        'POST',
        route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
        array_merge(vitoPestFeatureAPIAgentControllerTestMinimalPayload(), [
            'services' => [['id' => $service->id, 'status' => 'inactive']],
        ]),
        ['secret' => 'test-secret']
    )->assertSuccessful();

    $this->assertDatabaseHas('services', ['id' => $service->id, 'status' => ServiceStatus::READY]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('services must be an array', function () {
    $service = vitoPestFeatureAPIAgentControllerTestAgentService();

    $this->json(
        'POST',
        route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
        array_merge(vitoPestFeatureAPIAgentControllerTestMinimalPayload(), ['services' => 'nope']),
        ['secret' => 'test-secret']
    )->assertStatus(422);

    $this->assertDatabaseCount('metrics', 0);
});
