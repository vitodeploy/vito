<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('bootstrap dashboard URLs respect configuration without depending on the request host', function (array $configuration, array $urls) {
    config()->set([
        'horizon.domain' => null,
        'horizon.path' => 'horizon',
        'log-viewer.route_domain' => null,
        'log-viewer.route_path' => 'logs',
        ...$configuration,
    ]);

    $response = $this->actingAs($this->user)
        ->withHeader('X-Forwarded-Host', 'untrusted.example')
        ->getJson('https://panel.example/bootstrap')
        ->assertSuccessful()
        ->assertJsonPath('configs.dashboard_urls', $urls);

    $this->withHeader('X-Forwarded-Host', 'panel.example')
        ->getJson('https://panel.example/bootstrap')
        ->assertSuccessful()
        ->assertJsonPath('configs.dashboard_urls', $urls)
        ->assertJsonPath('version', $response->json('version'));
})->with([
    'default paths' => [
        [],
        ['horizon' => '/horizon', 'logs' => '/logs'],
    ],
    'custom paths' => [
        ['horizon.path' => '/queue-monitor/', 'log-viewer.route_path' => 'ops/logs'],
        ['horizon' => '/queue-monitor', 'logs' => '/ops/logs'],
    ],
    'configured domains' => [
        [
            'horizon.domain' => 'queues.example',
            'horizon.path' => 'queue-monitor',
            'log-viewer.route_domain' => 'logs.example:8443',
            'log-viewer.route_path' => 'ops/logs',
        ],
        ['horizon' => '//queues.example/queue-monitor', 'logs' => '//logs.example:8443/ops/logs'],
    ],
    'root paths' => [
        ['horizon.path' => '', 'log-viewer.route_path' => '/'],
        ['horizon' => '/', 'logs' => '/'],
    ],
]);
