<?php

use App\Enums\PHPIniType;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('install does not install global composer', function () {
    SSH::fake();
    Event::fake();

    $php = Service::factory()->create([
        'server_id' => $this->server->id,
        'type' => 'php',
        'type_data' => [
            'extensions' => [],
        ],
        'name' => 'php',
        'version' => '8.3',
        'status' => ServiceStatus::READY,
    ]);

    $php->handler()->install();

    Event::assertDispatched('service.installed');
    SSH::assertNotExecutedContains('getcomposer.org');
});

test('change default php cli', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $php = Service::factory()->create([
        'server_id' => $this->server->id,
        'type' => 'php',
        'type_data' => [
            'extensions' => [],
        ],
        'name' => 'php',
        'version' => '8.1',
        'status' => ServiceStatus::READY,
        'is_default' => false,
    ]);

    $this->post(route('php.default-cli', [
        'server' => $this->server,
        'service' => $php->id,
    ]), [
        'version' => '8.1',
    ])
        ->assertSessionDoesntHaveErrors();

    $php->refresh();

    expect($php->is_default)->toBeTrue();
});

test('extensions validation array extension', function () {
    SSH::fake('output... [PHP Modules] grcp');

    $this->actingAs($this->user);

    $php = $this->server->php('8.2');

    $php->type_data = [
        'available_extensions' => ['grcp'],
    ];
    $php->save();

    Event::listen('php.extensions.list', function (Service $service, array $availableExtensions) {
        return [
            'service' => $service,
            'available_extensions' => $service->type_data['available_extensions'] ?? $availableExtensions,
        ];
    });

    $this->post(route('php.install-extension', [
        'server' => $this->server,
        'service' => $php->id,
    ]), [
        'version' => '8.2',
        'extension' => 'grcp',
    ])
        ->assertSessionDoesntHaveErrors();

    expect($php->refresh()->type_data['extensions'])->toContain('grcp');
});

test('invalid extension validation', function () {
    SSH::fake('output... [PHP Modules] invalid');

    $this->actingAs($this->user);

    $php = $this->server->php('8.2');

    $this->post(route('php.install-extension', [
        'server' => $this->server,
        'service' => $php->id,
    ]), [
        'version' => '8.2',
        'extension' => 'invalid',
    ])
        ->assertSessionHasErrors([
            'extension' => 'The selected extension is invalid.',
        ]);
});

test('install extension', function () {
    SSH::fake('output... [PHP Modules] gmp');

    $this->actingAs($this->user);

    $php = $this->server->php('8.2');

    $this->post(route('php.install-extension', [
        'server' => $this->server,
        'service' => $php->id,
    ]), [
        'version' => '8.2',
        'extension' => 'gmp',
    ])
        ->assertSessionDoesntHaveErrors();

    expect($php->refresh()->type_data['extensions'])->toContain('gmp');
});

test('get php ini', function (string $version, PHPIniType $type) {
    SSH::fake('[PHP ini]');

    $this->actingAs($this->user);

    $php = $this->server->php($version);

    $this->get(route('php.ini', [
        'server' => $this->server,
        'service' => $php->id,
        'version' => $version,
        'type' => $type->value,
    ]))
        ->assertSessionDoesntHaveErrors();
})->with('php_ini_data');

dataset('php_ini_data', /** @return array<int, array{0: string, 1: PHPIniType}> */ function (): array {
    return [
        ['8.2', PHPIniType::FPM],
        ['8.2', PHPIniType::CLI],
    ];
});
