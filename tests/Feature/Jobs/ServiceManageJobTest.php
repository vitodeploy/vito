<?php

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Service\ManageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manage job handle sets ready on active response', function () {
    SSH::fake('Active: active');

    $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
    $service->status = ServiceStatus::RESTARTING;
    $service->save();

    $job = new ManageJob($service, 'restart', ServiceStatus::READY);
    $job->handle();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::READY);
});

test('manage job handle sets failed on inactive response', function () {
    SSH::fake('Active: inactive');

    $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
    $service->status = ServiceStatus::RESTARTING;
    $service->save();

    $job = new ManageJob($service, 'restart', ServiceStatus::READY);
    $job->handle();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
});

test('manage job stop expects inactive state', function () {
    SSH::fake('Active: inactive');

    $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
    $service->status = ServiceStatus::STOPPING;
    $service->save();

    $job = new ManageJob($service, 'stop', ServiceStatus::STOPPED);
    $job->handle();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::STOPPED);
});

test('manage job disable expects inactive state', function () {
    SSH::fake('Active: inactive');

    $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
    $service->status = ServiceStatus::DISABLING;
    $service->save();

    $job = new ManageJob($service, 'disable', ServiceStatus::DISABLED);
    $job->handle();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::DISABLED);
});

test('manage job failed sets status to failed and logs', function () {
    $service = $this->server->services()->where('name', 'nginx')->firstOrFail();
    $service->status = ServiceStatus::RESTARTING;
    $service->save();

    $job = new ManageJob($service, 'restart', ServiceStatus::READY);
    $job->failed(new Exception('SSH connection failed'));

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'restart-service-failed',
    ]);
});

test('manage job stop failed logs correct type', function () {
    $service = $this->server->services()->where('name', 'nginx')->firstOrFail();

    $job = new ManageJob($service, 'stop', ServiceStatus::STOPPED);
    $job->failed(new Exception('SSH connection failed'));

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'stop-service-failed',
    ]);
});
