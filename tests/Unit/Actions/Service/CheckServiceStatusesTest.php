<?php

namespace Tests\Unit\Actions\Service;

use App\Actions\Service\CheckServiceStatuses;
use App\Enums\ServiceStatus;
use App\Events\ServiceStatusChanged;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckServiceStatusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_changed_statuses_and_dispatches_events(): void
    {
        SSH::fake("active\ninactive\nfailed");
        Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

        $this->server->services()->delete();
        $nginx = $this->createService('webserver', 'nginx', ServiceStatus::READY);
        $mysql = $this->createService('database', 'mysql', ServiceStatus::READY);
        $redis = $this->createService('memory_database', 'redis', ServiceStatus::STOPPED);

        app(CheckServiceStatuses::class)->check($this->server);
        app(CheckServiceStatuses::class)->check($this->server);

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        $this->assertDatabaseHas('services', ['id' => $mysql->id, 'status' => ServiceStatus::STOPPED]);
        $this->assertDatabaseHas('services', ['id' => $redis->id, 'status' => ServiceStatus::FAILED]);

        Event::assertDispatched(fn (ServiceStatusChanged $event): bool => $event->service->id === $mysql->id
            && $event->previousStatus === ServiceStatus::READY
            && $event->newStatus === ServiceStatus::STOPPED);
        Event::assertDispatched(fn (ServiceStatusChanged $event): bool => $event->service->id === $redis->id
            && $event->previousStatus === ServiceStatus::STOPPED
            && $event->newStatus === ServiceStatus::FAILED);
        Event::assertNotDispatched(fn (ServiceStatusChanged $event): bool => $event->service->id === $nginx->id);
        Event::assertDispatchedTimes(SocketEvent::class, 2);
    }

    public function test_disabled_service_reported_inactive_stays_disabled(): void
    {
        SSH::fake('inactive');
        Event::fake([ServiceStatusChanged::class]);

        $this->server->services()->delete();
        $nginx = $this->createService('webserver', 'nginx', ServiceStatus::DISABLED);

        app(CheckServiceStatuses::class)->check($this->server);

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::DISABLED]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_disabled_service_reported_active_becomes_ready(): void
    {
        SSH::fake('active');
        Event::fake([ServiceStatusChanged::class]);

        $this->server->services()->delete();
        $nginx = $this->createService('webserver', 'nginx', ServiceStatus::DISABLED);

        app(CheckServiceStatuses::class)->check($this->server);

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        Event::assertDispatched(fn (ServiceStatusChanged $event): bool => $event->service->id === $nginx->id
            && $event->previousStatus === ServiceStatus::DISABLED
            && $event->newStatus === ServiceStatus::READY);
    }

    public function test_transitional_and_unitless_services_are_not_checked(): void
    {
        SSH::fake('active');
        Event::fake([ServiceStatusChanged::class]);

        $this->server->services()->delete();
        $this->createService('webserver', 'nginx', ServiceStatus::INSTALLING);
        $this->createService('nodejs', 'nodejs', ServiceStatus::READY);
        $this->createService('log_analysis', 'goaccess', ServiceStatus::READY);
        $mysql = $this->createService('database', 'mysql', ServiceStatus::STOPPED);

        app(CheckServiceStatuses::class)->check($this->server);

        SSH::assertExecutedContains('mysql');
        SSH::assertNotExecutedContains('nginx');
        SSH::assertNotExecutedContains('nodejs');
        SSH::assertNotExecutedContains('goaccess');
        SSH::assertNotExecutedContains("''");
        $this->assertDatabaseHas('services', ['id' => $mysql->id, 'status' => ServiceStatus::READY]);
        Event::assertDispatchedTimes(ServiceStatusChanged::class, 1);
    }

    public function test_skips_ssh_when_no_pollable_services(): void
    {
        SSH::fake('active');
        Event::fake([ServiceStatusChanged::class]);

        $this->server->services()->delete();
        $this->createService('nodejs', 'nodejs', ServiceStatus::READY);

        app(CheckServiceStatuses::class)->check($this->server);

        SSH::assertNotExecutedContains('is-active');
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_mismatched_output_updates_nothing(): void
    {
        SSH::fake('active');
        Event::fake([ServiceStatusChanged::class]);

        $this->server->services()->delete();
        $nginx = $this->createService('webserver', 'nginx', ServiceStatus::STOPPED);
        $mysql = $this->createService('database', 'mysql', ServiceStatus::STOPPED);

        app(CheckServiceStatuses::class)->check($this->server);

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::STOPPED]);
        $this->assertDatabaseHas('services', ['id' => $mysql->id, 'status' => ServiceStatus::STOPPED]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_single_inactive_reading_does_not_change_status(): void
    {
        SSH::fake('inactive');
        Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

        $this->server->services()->delete();
        $nginx = $this->createService('webserver', 'nginx', ServiceStatus::READY);

        app(CheckServiceStatuses::class)->check($this->server);

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
        Event::assertNotDispatched(SocketEvent::class);
    }

    public function test_restart_flap_does_not_change_status(): void
    {
        Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

        $this->server->services()->delete();
        $nginx = $this->createService('webserver', 'nginx', ServiceStatus::READY);

        SSH::fake('inactive');
        app(CheckServiceStatuses::class)->check($this->server);

        SSH::fake('active');
        app(CheckServiceStatuses::class)->check($this->server);

        SSH::fake('inactive');
        app(CheckServiceStatuses::class)->check($this->server);

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        Event::assertNotDispatched(ServiceStatusChanged::class);
    }

    public function test_two_consecutive_inactive_readings_change_status(): void
    {
        SSH::fake('inactive');
        Event::fake([ServiceStatusChanged::class]);

        $this->server->services()->delete();
        $nginx = $this->createService('webserver', 'nginx', ServiceStatus::READY);

        app(CheckServiceStatuses::class)->check($this->server);
        app(CheckServiceStatuses::class)->check($this->server);

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::STOPPED]);
        Event::assertDispatchedTimes(ServiceStatusChanged::class, 1);
    }

    public function test_recovery_to_active_applies_immediately(): void
    {
        SSH::fake('active');
        Event::fake([ServiceStatusChanged::class]);

        $this->server->services()->delete();
        $nginx = $this->createService('webserver', 'nginx', ServiceStatus::STOPPED);

        app(CheckServiceStatuses::class)->check($this->server);

        $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
        Event::assertDispatchedTimes(ServiceStatusChanged::class, 1);
    }

    private function createService(string $type, string $name, ServiceStatus $status): Service
    {
        /** @var Service $service */
        $service = $this->server->services()->create([
            'type' => $type,
            'name' => $name,
            'version' => 'latest',
            'status' => $status,
        ]);

        return $service;
    }
}
