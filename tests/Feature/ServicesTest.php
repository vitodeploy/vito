<?php

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see services list', function () {
    $this->actingAs($this->user);

    $this->get(route('services', [
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('services/index'));
});

test('services index returns the inertia table', function () {
    $this->actingAs($this->user);

    $this->get(route('services', ['server' => $this->server]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('services/index')
            ->where('refreshing', false)
            ->has('services.columns')
            ->has('services.data.0.resource')
            ->has('services.data.0.networked')
        );
});

test('restart service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();
    $service->status = ServiceStatus::STOPPED;
    $service->save();

    SSH::fake('Active: active');

    $this->post(route('services.restart', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::READY);
})->with('data');

test('reload service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();
    $service->status = ServiceStatus::READY;
    $service->save();

    SSH::fake('Active: active');

    $this->post(route('services.reload', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::READY);
})->with('data');

test('failed to reload service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();

    SSH::fake('Active: inactive');

    $this->post(route('services.reload', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
})->with('data');

test('failed to restart service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();

    SSH::fake('Active: inactive');

    $this->post(route('services.restart', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
})->with('data');

test('stop service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();

    SSH::fake('Active: inactive');

    $this->post(route('services.stop', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::STOPPED);
})->with('data');

test('failed to stop service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();

    SSH::fake('Active: active');

    $this->post(route('services.stop', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
})->with('data');

test('start service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();
    $service->status = ServiceStatus::STOPPED;
    $service->save();

    SSH::fake('Active: active');

    $this->post(route('services.start', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::READY);
})->with('data');

test('failed to start service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();

    SSH::fake('Active: inactive');

    $this->post(route('services.start', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
})->with('data');

test('enable service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();
    $service->status = ServiceStatus::DISABLED;
    $service->save();

    SSH::fake('Active: active');

    $this->post(route('services.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::READY);
})->with('data');

test('failed to enable service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();

    SSH::fake('Active: inactive');

    $this->post(route('services.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
})->with('data');

test('disable service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();

    SSH::fake('Active: inactive');

    $this->post(route('services.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::DISABLED);
})->with('data');

test('failed to disable service', function (string $name) {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', $name)->firstOrFail();

    SSH::fake('Active: active');

    $this->post(route('services.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertSessionDoesntHaveErrors();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
})->with('data');

test('install service', function (string $name, string $type, string $version) {
    Http::fake([
        'https://api.github.com/repos/vito/vito-agent/releases/latest' => Http::response([
            'tag_name' => '0.1.0',
        ]),
    ]);
    SSH::fake('Active: active');

    $this->actingAs($this->user);

    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $keys = $server->sshKey();
    if (! File::exists($keys['public_key_path']) || ! File::exists($keys['private_key_path'])) {
        $server->provider()->generateKeyPair();
    }

    $this->post(route('services.store', [
        'server' => $server,
    ]), [
        'name' => $name,
        'version' => $version,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('services', [
        'server_id' => $server->id,
        'name' => $name,
        'type' => $type,
        'status' => ServiceStatus::READY,
    ]);
})->with('installData');

test('install service creates installation log', function () {
    Http::fake([
        'https://api.github.com/repos/vito/vito-agent/releases/latest' => Http::response([
            'tag_name' => '0.1.0',
        ]),
    ]);
    SSH::fake('Active: active');

    $this->actingAs($this->user);

    $server = Server::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $keys = $server->sshKey();
    if (! File::exists($keys['public_key_path']) || ! File::exists($keys['private_key_path'])) {
        $server->provider()->generateKeyPair();
    }

    $this->post(route('services.store', [
        'server' => $server,
    ]), [
        'name' => 'redis',
        'version' => 'latest',
    ])
        ->assertSessionDoesntHaveErrors();

    $service = $server->services()->where('name', 'redis')->firstOrFail();

    // Verify that the installation log is linked to the service
    expect($service->log_id)->not->toBeNull();
    expect($service->log)->not->toBeNull();
    expect($service->log->type)->toStartWith('install-redis');

    expect($service->type_data['networking_effective'] ?? null)->toBeNull('Install never observes the live bind state, so it must not claim one.');
    expect($service->type_data['networking_checked_at'] ?? null)->toBeNull();
    $this->assertArrayNotHasKey('networking', $service->type_data);
});

test('parse php installed version', function (string $sshOutput, string $expectedVersion) {
    /** @var Service $service */
    $service = $this->server->services()->where('name', 'php')->firstOrFail();

    expect($service->handler()->parseVersionOutput($sshOutput))->toEqual($expectedVersion);
})->with('phpVersionOutputData');

test('version falls back to the raw output when it cannot be parsed', function () {
    SSH::fake('no version here');

    /** @var Service $service */
    $service = $this->server->services()->where('name', 'php')->firstOrFail();

    expect($service->handler()->version())->toEqual('no version here');
});

test('php version command escapes the configured version', function () {
    /** @var Service $service */
    $service = $this->server->services()->where('name', 'php')->firstOrFail();
    $service->version = "8.3'; rm -rf /tmp; echo '";

    expect($service->handler()->versionCommand())->toEqual('/usr/bin/php\'8.3\'\\\'\'; rm -rf /tmp; echo \'\\\'\'\' -r \'echo PHP_VERSION;\' 2>/dev/null');
});

test('services version route is removed', function () {
    expect(Route::has('services.version'))->toBeFalse();
});

/**
 * @return array<array<string>>
 */
dataset('phpVersionOutputData', function () {
    return [
        'clean version' => ['8.4.10', '8.4.10'],
        'version with noise' => ["Deprecated: some deprecation notice in php\n8.5.2", '8.5.2'],
        'version with whitespace' => ["  8.5.1\n", '8.5.1'],
    ];
});

/**
 * @return array<array<string>>
 */
dataset('data', function () {
    return [
        ['nginx'],
        ['php'],
        ['supervisor'],
        ['redis'],
        ['mysql'],
    ];
});

/**
 * @return array<array<string>>
 */
dataset('installData', function () {
    return [
        [
            'nginx',
            'webserver',
            'latest',
        ],
        [
            'caddy',
            'webserver',
            'latest',
        ],
        [
            'php',
            'php',
            '7.4',
        ],
        [
            'nodejs',
            'nodejs',
            '16',
        ],
        [
            'supervisor',
            'process_manager',
            'latest',
        ],
        [
            'goaccess',
            'log_analysis',
            'latest',
        ],
        [
            'redis',
            'memory_database',
            'latest',
        ],
        [
            'valkey',
            'memory_database',
            'latest',
        ],
        [
            'mysql',
            'database',
            '8.4',
        ],
        [
            'mariadb',
            'database',
            '10.11',
        ],
        [
            'postgresql',
            'database',
            '16',
        ],
    ];
});
