<?php

use App\Enums\OperatingSystem;
use App\Enums\PHPIniType;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Server;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

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

test('install uses the php repository for the server os', function (OperatingSystem $os, string $repository, string $otherRepository) {
    SSH::fake();
    Event::fake();

    $this->server->update(['os' => $os]);

    $php = Service::factory()->create([
        'server_id' => $this->server->id,
        'type' => 'php',
        'type_data' => [
            'extensions' => [],
        ],
        'name' => 'php',
        'version' => '8.4',
        'status' => ServiceStatus::READY,
    ]);

    $php->handler()->install();

    SSH::assertExecutedContains($repository);
    SSH::assertNotExecutedContains($otherRepository);
})->with([
    'ubuntu 24' => [OperatingSystem::UBUNTU24, 'ppa:ondrej/php', 'packages.sury.org'],
    'ubuntu 26' => [OperatingSystem::UBUNTU26, 'https://packages.sury.org/php/', 'ppa:ondrej/php'],
]);

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

test('php routes reject a service from another server', function (string $method, string $route) {
    $ssh = SSH::fake();
    Queue::fake();

    $otherServer = Server::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->server->project_id,
    ]);
    $service = $this->server->php('8.2');
    $attributes = $service->refresh()->getAttributes();
    $targetService = $service->replicate();
    $targetService->server_id = $otherServer->id;
    $targetService->is_default = false;
    $targetService->save();
    $targetAttributes = $targetService->refresh()->getAttributes();

    $this->actingAs($this->user)->json($method, route($route, [
        'server' => $otherServer,
        'service' => $service,
    ]), [
        'version' => '8.2',
        'type' => PHPIniType::CLI->value,
        'ini' => '[PHP]',
        'extension' => 'gmp',
    ])->assertForbidden();

    expect($service->refresh()->getAttributes())->toBe($attributes);
    expect($targetService->refresh()->getAttributes())->toBe($targetAttributes);
    expect($ssh->getExecutedCommands())->toBeEmpty();
    expect($ssh->getUploadedContent())->toBeEmpty();
    Queue::assertNothingPushed();
})->with([
    ['GET', 'php.ini'],
    ['PATCH', 'php.ini.update'],
    ['POST', 'php.install-extension'],
    ['POST', 'php.default-cli'],
]);
