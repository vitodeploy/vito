<?php

use App\Facades\SSH;
use App\SiteTypes\Laravel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('enable modern deployment', function () {
    SSH::fake();

    Http::fake([
        'https://api.github.com/repos/*' => Http::response([
        ], 201),
    ]);

    $this->site->update([
        'type' => Laravel::id(),
    ]);

    $this->actingAs($this->user)
        ->post(route('site-features.action', [
            'server' => $this->server,
            'site' => $this->site,
            'feature' => 'modern-deployment',
            'action' => 'enable',
        ]), [
            'shared_resources' => '.env',
            'history' => 10,
        ])
        ->assertRedirect();

    $this->site->refresh();

    expect($this->site->type_data['modern_deployment'])->toBeTrue();
    expect($this->site->type_data['modern_deployment_history'])->toEqual('10');
    expect($this->site->type_data['modern_deployment_shared_resources'])->toEqual(['.env']);
});

test('enabling modern deployment inherits restart workers flag into pre flight', function () {
    SSH::fake();

    Http::fake([
        'https://api.github.com/repos/*' => Http::response([], 201),
    ]);

    $this->site->update([
        'type' => Laravel::id(),
    ]);
    $this->site->ensureDeploymentScriptsExist();
    $this->site->deploymentScript->update(['configs' => ['restart_workers' => true]]);

    $this->actingAs($this->user)
        ->post(route('site-features.action', [
            'server' => $this->server,
            'site' => $this->site,
            'feature' => 'modern-deployment',
            'action' => 'enable',
        ]), [
            'shared_resources' => '.env',
            'history' => 10,
        ])
        ->assertRedirect();

    $this->site->refresh();

    expect($this->site->preFlightScript)->not->toBeNull();
    expect($this->site->preFlightScript->shouldRestartWorkers())->toBeTrue();
    expect($this->site->deploymentScriptFor(true)->shouldRestartWorkers())->toBeTrue();
    expect($this->site->deploymentScriptFor(false)->shouldRestartWorkers())->toBeTrue();
});

test('disable modern deployment', function () {
    SSH::fake();

    Http::fake([
        'https://api.github.com/repos/*' => Http::response([
        ], 201),
    ]);

    $this->site->update([
        'type' => Laravel::id(),
        'type_data' => [
            'modern_deployment' => true,
            'modern_deployment_history' => 10,
            'modern_deployment_shared_resources' => ['.env'],
        ],
    ]);

    $this->actingAs($this->user)
        ->post(route('site-features.action', [
            'server' => $this->server,
            'site' => $this->site,
            'feature' => 'modern-deployment',
            'action' => 'disable',
        ]))
        ->assertRedirect();

    $this->site->refresh();

    expect($this->site->type_data['modern_deployment'] ?? false)->toBeFalse();
    $this->assertArrayNotHasKey('modern_deployment_history', $this->site->type_data);
    $this->assertArrayNotHasKey('modern_deployment_shared_resources', $this->site->type_data);
});

test('configure modern deployment', function () {
    $this->site->update([
        'type' => Laravel::id(),
        'type_data' => [
            'modern_deployment' => true,
            'modern_deployment_history' => 10,
            'modern_deployment_shared_resources' => ['.env'],
        ],
    ]);

    $this->actingAs($this->user)
        ->post(route('site-features.action', [
            'server' => $this->server,
            'site' => $this->site,
            'feature' => 'modern-deployment',
            'action' => 'configuration',
        ]), [
            'shared_resources' => '.env,.env.local',
            'history' => 5,
        ])
        ->assertRedirect();

    $this->site->refresh();

    expect($this->site->type_data['modern_deployment'])->toBeTrue();
    expect($this->site->type_data['modern_deployment_history'])->toEqual('5');
    expect($this->site->type_data['modern_deployment_shared_resources'])->toEqual(['.env', '.env.local']);
});
