<?php

use App\Actions\Server\InstallServer;
use App\Enums\ServerStatus;
use App\Exceptions\SSHConnectionError;
use App\Facades\SSH;
use App\Jobs\Server\InstallJob;
use App\Models\ServerProvider;
use App\ServerProviders\DigitalOcean;
use Carbon\CarbonInterval as Duration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;

uses(RefreshDatabase::class);

test('install job failed sets status to installation failed and logs', function () {
    Notification::fake();

    $this->server->update(['status' => ServerStatus::INSTALLING]);

    $job = new InstallJob($this->server);
    $job->failed(new Exception('Installation failed'));

    $this->server->refresh();

    expect($this->server->status)->toEqual(ServerStatus::INSTALLATION_FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'server-installation-failed',
    ]);
});

test('waiting for the provider is bounded and paced when the instance never becomes active', function () {
    SSH::fake();

    $polls = 0;
    Http::fake([
        'api.digitalocean.com/v2/droplets/*' => function () use (&$polls) {
            $polls++;

            if ($polls > 50) {
                throw new Error('isRunning() was polled '.$polls.' times: the wait loop is unbounded.');
            }

            return Http::response([
                'droplet' => [
                    'id' => 599103218,
                    'status' => 'new',
                    'networks' => ['v4' => []],
                ],
            ]);
        },
        '*' => Http::response([]),
    ]);

    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => DigitalOcean::id(),
        'credentials' => ['token' => 'secret-token'],
    ]);

    $this->server->update([
        'provider' => DigitalOcean::id(),
        'provider_id' => $serverProvider->id,
        'provider_data' => ['plan' => 's-1vcpu-512mb-10gb', 'region' => 'nyc1', 'droplet_id' => 599103218],
        'ip' => '',
        'status' => ServerStatus::INSTALLING,
    ]);

    expect(fn () => app(InstallServer::class)->run($this->server->refresh()))
        ->toThrow(SSHConnectionError::class, 'The server did not become reachable within 180 seconds.');

    expect($polls)->toBe(18);

    Sleep::assertSleptTimes(18);
    Sleep::assertSlept(fn (Duration $duration) => (int) $duration->totalSeconds === 10, 18);

    $this->server->refresh();

    expect($this->server->status)->toEqual(ServerStatus::INSTALLING);
});

test('the underlying ssh failure is preserved when the server never becomes reachable', function () {
    SSH::fake()->connectionWillFail();

    Http::fake([
        'api.digitalocean.com/v2/droplets/*' => Http::response([
            'droplet' => [
                'id' => 599103218,
                'status' => 'active',
                'networks' => ['v4' => [['type' => 'public', 'ip_address' => '164.92.1.1']]],
            ],
        ]),
        '*' => Http::response([]),
    ]);

    $serverProvider = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => DigitalOcean::id(),
        'credentials' => ['token' => 'secret-token'],
    ]);

    $this->server->update([
        'provider' => DigitalOcean::id(),
        'provider_id' => $serverProvider->id,
        'provider_data' => ['plan' => 's-1vcpu-512mb-10gb', 'region' => 'nyc1', 'droplet_id' => 599103218],
        'ip' => '',
        'status' => ServerStatus::INSTALLING,
    ]);

    try {
        app(InstallServer::class)->run($this->server->refresh());

        $this->fail('Expected InstallServer to throw.');
    } catch (SSHConnectionError $e) {
        expect($e->getMessage())->toBe('The server did not become reachable within 180 seconds.')
            ->and($e->getPrevious())->toBeInstanceOf(SSHConnectionError::class)
            ->and($e->getPrevious()?->getMessage())->toBe('Connection failed');
    }
});
