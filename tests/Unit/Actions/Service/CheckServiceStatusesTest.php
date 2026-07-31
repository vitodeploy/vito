<?php

use App\Actions\Service\CheckServiceStatuses;
use App\Enums\ServiceStatus;
use App\Events\ServiceStatusChanged;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->server->services()->delete();
});

test('updates changed statuses and dispatches events', function () {
    SSH::fake("active\ninactive\nfailed");
    Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

    $nginx = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('webserver', 'nginx', ServiceStatus::READY);
    $mysql = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('database', 'mysql', ServiceStatus::READY);
    $redis = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('memory_database', 'redis', ServiceStatus::STOPPED);

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
});

test('disabled service reported inactive stays disabled', function () {
    SSH::fake('inactive');
    Event::fake([ServiceStatusChanged::class]);

    $nginx = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('webserver', 'nginx', ServiceStatus::DISABLED);

    app(CheckServiceStatuses::class)->check($this->server);

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::DISABLED]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('disabled service reported active becomes ready', function () {
    SSH::fake('active');
    Event::fake([ServiceStatusChanged::class]);

    $nginx = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('webserver', 'nginx', ServiceStatus::DISABLED);

    app(CheckServiceStatuses::class)->check($this->server);

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
    Event::assertDispatched(fn (ServiceStatusChanged $event): bool => $event->service->id === $nginx->id
        && $event->previousStatus === ServiceStatus::DISABLED
        && $event->newStatus === ServiceStatus::READY);
});

test('transitional and unitless services are not checked', function () {
    SSH::fake('active');
    Event::fake([ServiceStatusChanged::class]);

    vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('webserver', 'nginx', ServiceStatus::INSTALLING);
    vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('nodejs', 'nodejs', ServiceStatus::READY);
    vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('log_analysis', 'goaccess', ServiceStatus::READY);
    $mysql = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('database', 'mysql', ServiceStatus::STOPPED);

    app(CheckServiceStatuses::class)->check($this->server);

    SSH::assertExecutedContains('mysql');
    SSH::assertNotExecutedContains('nginx');
    SSH::assertNotExecutedContains('nodejs');
    SSH::assertNotExecutedContains('goaccess');
    SSH::assertNotExecutedContains("''");
    $this->assertDatabaseHas('services', ['id' => $mysql->id, 'status' => ServiceStatus::READY]);
    Event::assertDispatchedTimes(ServiceStatusChanged::class, 1);
});

test('skips ssh when no pollable services', function () {
    SSH::fake('active');
    Event::fake([ServiceStatusChanged::class]);

    vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('nodejs', 'nodejs', ServiceStatus::READY);

    app(CheckServiceStatuses::class)->check($this->server);

    SSH::assertNotExecutedContains('is-active');
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('mismatched output updates nothing', function () {
    SSH::fake('active');
    Event::fake([ServiceStatusChanged::class]);

    $nginx = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('webserver', 'nginx', ServiceStatus::STOPPED);
    $mysql = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('database', 'mysql', ServiceStatus::STOPPED);

    app(CheckServiceStatuses::class)->check($this->server);

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::STOPPED]);
    $this->assertDatabaseHas('services', ['id' => $mysql->id, 'status' => ServiceStatus::STOPPED]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('single inactive reading does not change status', function () {
    SSH::fake('inactive');
    Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

    $nginx = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('webserver', 'nginx', ServiceStatus::READY);

    app(CheckServiceStatuses::class)->check($this->server);

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
    Event::assertNotDispatched(SocketEvent::class);
});

test('restart flap does not change status', function () {
    Event::fake([ServiceStatusChanged::class, SocketEvent::class]);

    $nginx = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('webserver', 'nginx', ServiceStatus::READY);

    SSH::fake('inactive');
    app(CheckServiceStatuses::class)->check($this->server);

    SSH::fake('active');
    app(CheckServiceStatuses::class)->check($this->server);

    SSH::fake('inactive');
    app(CheckServiceStatuses::class)->check($this->server);

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
    Event::assertNotDispatched(ServiceStatusChanged::class);
});

test('two consecutive inactive readings change status', function () {
    SSH::fake('inactive');
    Event::fake([ServiceStatusChanged::class]);

    $nginx = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('webserver', 'nginx', ServiceStatus::READY);

    app(CheckServiceStatuses::class)->check($this->server);
    app(CheckServiceStatuses::class)->check($this->server);

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::STOPPED]);
    Event::assertDispatchedTimes(ServiceStatusChanged::class, 1);
});

test('recovery to active applies immediately', function () {
    SSH::fake('active');
    Event::fake([ServiceStatusChanged::class]);

    $nginx = vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService('webserver', 'nginx', ServiceStatus::STOPPED);

    app(CheckServiceStatuses::class)->check($this->server);

    $this->assertDatabaseHas('services', ['id' => $nginx->id, 'status' => ServiceStatus::READY]);
    Event::assertDispatchedTimes(ServiceStatusChanged::class, 1);
});

function vitoPestUnitActionsServiceCheckServiceStatusesTestCreateService(string $type, string $name, ServiceStatus $status): Service
{
    /** @var Service $service */
    $service = test()->server->services()->create([
        'type' => $type,
        'name' => $name,
        'version' => 'latest',
        'status' => $status,
    ]);

    return $service;
}
