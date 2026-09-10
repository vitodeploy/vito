<?php

use App\DTOs\ServiceLog;
use App\Services\Database\Clickhouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $database = $this->server->database();
    $database->name = 'clickhouse';
    $database->version = '24.8';
    $database->save();

    /** @var Clickhouse $handler */
    $handler = $database->handler();
    $this->clickhouse = $handler;
});

test('id returns clickhouse', function () {
    expect(Clickhouse::id())->toBe('clickhouse')
        ->and(Clickhouse::type())->toBe('database');
});

test('unit returns clickhouse-server', function () {
    expect($this->clickhouse->unit())->toBe('clickhouse-server');
});

test('version command is defined', function () {
    expect($this->clickhouse->versionCommand())->toContain('clickhouse-client --version');
});

test('networking configuration', function () {
    expect($this->clickhouse->networkingPort())->toBe(9000)
        ->and($this->clickhouse->usesHost())->toBeFalse()
        ->and($this->clickhouse->networkingProbeRequiresRunning())->toBeTrue()
        ->and($this->clickhouse->networkingProbeCommand())->toContain('system.server_settings')
        ->and($this->clickhouse->parseNetworkingProbe('0.0.0.0'))->toBeTrue()
        ->and($this->clickhouse->parseNetworkingProbe('127.0.0.1'))->toBeFalse()
        ->and($this->clickhouse->parseNetworkingProbe(''))->toBeNull();
});

test('logs returns service journal', function () {
    $logs = $this->clickhouse->logs();

    expect($logs)->toHaveCount(1)
        ->and($logs[0])->toBeInstanceOf(ServiceLog::class)
        ->and($logs[0]->key)->toBe('clickhouse:journal')
        ->and($logs[0]->serviceLabel)->toBe('ClickHouse')
        ->and($logs[0]->target)->toBe('clickhouse-server.service');
});

test('clickhouse is registered in service configs', function () {
    $services = config('service.services');

    expect($services)->toHaveKey('clickhouse')
        ->and($services['clickhouse']['type'])->toBe('database')
        ->and($services['clickhouse']['versions'])->toContain('24.8', '24.3', '23.8');
});
