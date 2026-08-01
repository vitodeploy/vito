<?php

use App\Enums\ServiceStatus;
use App\Models\Metric;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('visit metrics', function () {
    $this->actingAs($this->user);

    Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'vito-agent',
        'type' => 'monitoring',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    $this->get(route('monitoring', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('monitoring/index'));
});

test('update data retention', function () {
    $this->actingAs($this->user);

    Service::factory()->create([
        'server_id' => $this->server->id,
        'name' => 'vito-agent',
        'type' => 'monitoring',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    $this->patch(route('monitoring.update', $this->server), [
        'data_retention' => 365,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('services', [
        'server_id' => $this->server->id,
        'type' => 'monitoring',
        'type_data->data_retention' => 365,
    ]);
});

test('monitoring json returns current and history', function () {
    $this->actingAs($this->user);

    Metric::factory()->create([
        'server_id' => $this->server->id,
        'cpu_cores' => 4,
        'cpu_physical_cores' => 2,
        'cpu_usage_percent' => 12.34,
        'memory_total' => 2000,
        'memory_used' => 1010,
        'memory_free' => 990,
        'swap_used_percent' => 0,
        'disk_total' => 1000,
        'disk_used' => 250,
        'disk_free' => 750,
        'uptime_seconds' => 3600,
        'reboot_required' => false,
    ]);

    $response = $this->getJson(route('monitoring.json', ['server' => $this->server, 'period' => '10m']));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'current' => [
                'date',
                'cpu_cores',
                'cpu_physical_cores',
                'cpu_usage_percent',
                'memory_used_percent',
                'swap_used_percent',
                'disk_used_percent',
                'uptime_seconds',
                'reboot_required',
            ],
            'history' => [],
        ])
        ->assertJsonPath('current.cpu_cores', 4)
        ->assertJsonPath('current.cpu_usage_percent', 12.34)
        ->assertJsonPath('current.disk_used_percent', 25)
        ->assertJsonPath('current.memory_used_percent', 50.5)
        ->assertJsonPath('current.uptime_seconds', 3600);

    $history = $response->json('history');
    expect($history)->toBeArray();
    expect($history)->not->toBeEmpty();
    foreach (['cpu_cores', 'cpu_physical_cores', 'uptime_seconds', 'reboot_required', 'date_interval'] as $excluded) {
        $this->assertArrayNotHasKey($excluded, $history[0]);
    }
    expect($history[0])->toHaveKey('load');
    expect($history[0])->toHaveKey('cpu_usage_percent');
    expect($history[0])->toHaveKey('disk_used_percent');
    expect($history[0]['disk_used_percent'])->toEqual(25.0);
    expect($history[0])->toHaveKey('memory_used_percent');
    expect($history[0]['memory_used_percent'])->toEqual(50.5);

    foreach (['load', 'memory_total', 'cpu_usage_percent', 'disk_used_percent'] as $key) {
        if ($history[0][$key] !== null) {
            expect($history[0][$key])->toBeNumeric("history.0.{$key} should be numeric (not a string)");
            $this->assertIsNotString($history[0][$key], "history.0.{$key} must not be serialised as a JSON string");
        }
    }
});

test('monitoring json returns null current when no metrics', function () {
    $this->actingAs($this->user);

    $this->getJson(route('monitoring.json', ['server' => $this->server, 'period' => '10m']))
        ->assertSuccessful()
        ->assertJsonPath('current', null)
        ->assertJsonPath('history', []);
});

test('monitoring json defaults period when missing', function () {
    $this->actingAs($this->user);

    $this->getJson(route('monitoring.json', ['server' => $this->server]))
        ->assertSuccessful()
        ->assertJsonPath('current', null)
        ->assertJsonPath('history', []);
});
