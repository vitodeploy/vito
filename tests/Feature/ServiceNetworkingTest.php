<?php

use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Http\Resources\ServiceResource;
use App\Jobs\Service\ToggleNetworkingJob;
use App\Models\Service;
use App\Support\Testing\SSHFake;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('enable networking', function (string $name) {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase($name);

    SSH::fake("Active: active\nbind 0.0.0.0");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeTrue();
    expect($service->type_data['networking_effective'])->toBeTrue();
    expect($service->type_data['networking_checked_at'])->not->toBeNull();
    expect($service->status)->toEqual(ServiceStatus::READY);
    expect($service->secret)->not->toBeNull();

    SSH::assertExecutedContains("sudo cp /etc/{$name}/{$name}.conf /etc/{$name}/{$name}.conf.vito.bak");
    SSH::assertNotExecutedContains('.vito.bak || true', 'A failed backup must abort the write, not be suppressed.');
    SSH::assertNotExecutedContains("[ -f /etc/{$name}/", 'Guards must run under sudo — the conf directory is not readable by the SSH user.');
    SSH::assertExecutedContains('bind[[:space:]]+)(0\.0\.0\.0|\*).*$/\1127.0.0.1/');
    SSH::assertExecutedContains("sudo cp /etc/{$name}/vito-networking.conf /etc/{$name}/vito-networking.conf.vito.bak");
    SSH::assertExecutedContains("sudo install -o {$name} -g {$name} -m 600 /dev/null /etc/{$name}/vito-networking.conf");
    SSH::assertExecutedContains('printf \'requirepass "%s"\n\' "$VITO_MEMDB_PASSWORD"');
    SSH::assertExecutedContains('sudo sed -i \'/^# BEGIN VITO NETWORKING$/,/^# END VITO NETWORKING$/d\'');
    SSH::assertExecutedContains('# BEGIN VITO NETWORKING\nbind 0.0.0.0\ninclude %s\n# END VITO NETWORKING\n');
    SSH::assertExecutedContains("sudo systemctl restart {$name}-server");
    SSH::assertNotExecutedContains((string) $service->secret, 'The networking password must never appear in a command.');
})->with('memoryDatabases');

test('disable networking', function (string $name) {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase($name);
    $service->type_data = ['networking' => true];
    $service->secret = 'existing-secret';
    $service->save();

    SSH::fake("Active: active\nbind 127.0.0.1");

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeFalse();
    expect($service->type_data['networking_effective'])->toBeFalse();
    expect($service->type_data['networking_checked_at'])->not->toBeNull();
    expect($service->secret)->toEqual('existing-secret');
    expect($service->status)->toEqual(ServiceStatus::READY);

    SSH::assertExecutedContains('# BEGIN VITO NETWORKING\nbind 127.0.0.1\ninclude %s\n# END VITO NETWORKING\n');
    SSH::assertExecutedContains("sudo test -f /etc/{$name}/vito-networking.conf || sudo install -o {$name}");
    SSH::assertExecutedContains("sudo systemctl restart {$name}-server");
    SSH::assertNotExecutedContains('bind 0.0.0.0');
    SSH::assertNotExecutedContains("[ -f /etc/{$name}/", 'An unprivileged guard would truncate the include file and drop the password.');
})->with('memoryDatabases');

test('enable persists the secret before dispatching the job', function () {
    Queue::fake();
    SSH::fake();

    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    Queue::assertPushed(ToggleNetworkingJob::class);
    expect($service->status)->toEqual(ServiceStatus::RESTARTING);
    expect(strlen((string) $service->secret))->toEqual(32);

    $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson([
            'supported' => true,
            'pending' => true,
            'enabled' => false,
            'port' => 6379,
            'secret' => $service->secret,
            'effective' => null,
        ]);

    SSH::assertNotExecutedContains('grep -E', 'The host must not be read while the toggle is pending.');
});

test('enable keeps the existing secret', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->secret = 'existing-secret';
    $service->save();

    SSH::fake("Active: active\nbind 0.0.0.0");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    expect($service->refresh()->secret)->toEqual('existing-secret');
});

test('secret is encrypted at rest and never exposed by the resource', function () {
    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->secret = 'plain-text-secret';
    $service->type_data = ['networking' => true];
    $service->save();
    $service->refresh();

    $this->assertNotEquals('plain-text-secret', $service->getRawOriginal('secret'));
    expect($service->secret)->toEqual('plain-text-secret');

    $payload = (new ServiceResource($service))->toArray(request());

    $this->assertArrayNotHasKey('secret', $payload);
    $this->assertStringNotContainsString('plain-text-secret', (string) json_encode($payload));
    expect($payload['supports_networking'])->toBeTrue();
    expect($payload['networking_enabled'])->toBeTrue();
});

test('resource does not support networking for other services', function () {
    $service = $this->server->services()->where('name', 'nginx')->firstOrFail();

    $payload = (new ServiceResource($service))->toArray(request());

    expect($payload['supports_networking'])->toBeFalse();
    expect($payload['networking_enabled'])->toBeFalse();
});

test('enable networking skips the restart when the service is not running', function (string $status) {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->status = ServiceStatus::from($status);
    $service->save();

    SSH::fake();

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeTrue();
    expect($service->type_data['networking_effective'])->toBeTrue();
    expect($service->type_data['networking_checked_at'] ?? null)->toBeNull('A toggle that never restarted or verified must not stamp an observation timestamp.');
    expect($service->status)->toEqual(ServiceStatus::from($status));

    SSH::assertExecutedContains('# BEGIN VITO NETWORKING');
    SSH::assertNotExecutedContains('systemctl restart');
    SSH::assertNotExecutedContains('grep -E');
})->with('settledStatuses');

test('cannot toggle networking while the status is not settled', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->status = ServiceStatus::RESTARTING;
    $service->save();

    SSH::fake();

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertStatus(422);

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertStatus(422);

    expect($service->refresh()->type_data)->toBeNull();
});

test('unsupported service cannot toggle networking', function () {
    $this->actingAs($this->user);

    $service = $this->server->services()->where('name', 'nginx')->firstOrFail();

    SSH::fake();

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertStatus(422);

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertStatus(422);

    $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson(['supported' => false]);
});

test('service without a handler does not break networking or the index', function () {
    $this->actingAs($this->user);

    $service = Service::factory()->create([
        'server_id' => $this->server->id,
        'type' => 'memory_database',
        'name' => 'ghost',
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    SSH::fake();

    $this->get(route('services', ['server' => $this->server]))->assertSuccessful();

    $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson(['supported' => false]);

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertStatus(422);
});

test('user role cannot manage networking', function () {
    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');

    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    $this->actingAs($this->user);

    SSH::fake();

    $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertForbidden();

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertForbidden();

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertForbidden();
});

test('networking details report the effective host state', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->type_data = [
        'networking_effective' => true,
        'networking_checked_at' => '2026-07-26T12:00:00+00:00',
    ];
    $service->save();

    SSH::fake();

    $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson([
            'supported' => true,
            'pending' => false,
            'enabled' => false,
            'effective' => true,
            'checked_at' => '2026-07-26T12:00:00+00:00',
            'port' => 6379,
        ]);

    SSH::assertNotExecutedContains("sudo grep -E '^[[:space:]]*bind' /etc/redis/redis.conf | tail -1 || true");
});

test('networking details report unknown when never probed', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');

    $response = $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson([
            'supported' => true,
            'pending' => false,
            'enabled' => false,
            'effective' => null,
            'checked_at' => null,
            'port' => 6379,
        ]);

    $this->assertArrayNotHasKey('error', $response->json());
});

test('failed enable rolls back and marks the service as failed', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');

    SSH::fake("Active: active\nbind 127.0.0.1");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
    expect($service->type_data['networking'])->toBeFalse();
    expect($service->type_data['networking_failed'])->toBeTrue();
    expect($service->type_data['networking_effective'])->toBeNull();

    SSH::assertExecutedContains('sudo cp /etc/redis/redis.conf.vito.bak /etc/redis/redis.conf');
    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'enable-networking-failed',
    ]);
});

test('failed config write rolls back and marks the service as failed', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');

    SSH::swap(new class extends SSHFake
    {
        public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null, int $timeout = 0): string
        {
            if (str((string) $command)->contains('requirepass')) {
                throw new SSHCommandError('Connection failed');
            }

            return parent::exec($command, $log, $siteId, $stream, $streamCallback, $timeout);
        }
    });

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
    expect($service->type_data['networking'])->toBeFalse();
    expect($service->type_data['networking_failed'])->toBeTrue();
    expect($service->type_data['networking_effective'])->toBeNull();

    SSH::assertExecutedContains('sudo cp /etc/redis/redis.conf.vito.bak /etc/redis/redis.conf');
    SSH::assertExecutedContains('sudo cp /etc/redis/vito-networking.conf.vito.bak /etc/redis/vito-networking.conf');
    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'enable-networking-failed',
    ]);
});

test('failed rollback is logged', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');

    SSH::swap(new class extends SSHFake
    {
        public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null, int $timeout = 0): string
        {
            if (str((string) $command)->contains('.vito.bak /etc/redis/redis.conf')) {
                throw new SSHCommandError('Rollback failed');
            }

            return parent::exec($command, $log, $siteId, $stream, $streamCallback, $timeout);
        }
    });

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'rollback-redis-networking-failed',
    ]);
    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'enable-networking-failed',
    ]);
});

test('networking details report management and password capability', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');

    SSH::fake('bind 127.0.0.1');

    $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson([
            'managed' => false,
            'failed' => false,
            'uses_password' => true,
        ]);

    $service->type_data = ['networking' => false];
    $service->status = ServiceStatus::FAILED;
    $service->save();

    $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson([
            'managed' => true,
            'failed' => false,
        ]);

    $service->type_data = ['networking' => false, 'networking_failed' => true];
    $service->save();

    $this->getJson(route('services.networking', [
        'server' => $this->server,
        'service' => $service->id,
    ]))
        ->assertOk()
        ->assertJson(['failed' => true]);
});

test('a successful toggle clears a previous networking failure', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->type_data = ['networking' => false, 'networking_failed' => true];
    $service->save();

    SSH::fake("Active: active\nbind 0.0.0.0");

    $this->postJson(route('services.networking.enable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->type_data['networking'])->toBeTrue();
    $this->assertArrayNotHasKey('networking_failed', $service->type_data);
});

test('failed disable does not roll back', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->type_data = ['networking' => true];
    $service->secret = 'existing-secret';
    $service->save();

    SSH::fake("Active: active\nbind 0.0.0.0");

    $this->postJson(route('services.networking.disable', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->status)->toEqual(ServiceStatus::FAILED);
    expect($service->type_data['networking'])->toBeTrue();
    expect($service->type_data['networking_effective'])->toBeNull();

    SSH::assertNotExecutedContains('sudo cp /etc/redis/redis.conf.vito.bak /etc/redis/redis.conf');
});

test('regenerate the networking password', function (string $name) {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase($name);
    $service->type_data = ['networking' => true, 'networking_effective' => true];
    $service->secret = 'existing-secret';
    $service->save();

    SSH::fake('Active: active');

    $this->postJson(route('services.networking.secret.regenerate', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect(strlen((string) $service->secret))->toEqual(32);
    $this->assertNotEquals('existing-secret', $service->secret);
    expect($service->type_data['networking'])->toBeTrue();
    expect($service->status)->toEqual(ServiceStatus::READY);

    SSH::assertExecutedContains("sudo install -o {$name} -g {$name} -m 600 /dev/null /etc/{$name}/vito-networking.conf");
    SSH::assertExecutedContains('printf \'requirepass "%s"\n\' "$VITO_MEMDB_PASSWORD"');
    SSH::assertExecutedContains("sudo systemctl restart {$name}-server");
    SSH::assertNotExecutedContains('# BEGIN VITO NETWORKING', 'Regenerating a password must not rewrite the bind configuration.');
    SSH::assertNotExecutedContains((string) $service->secret, 'The networking password must never appear in a command.');
})->with('memoryDatabases');

test('remove the networking password', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->type_data = ['networking' => false, 'networking_effective' => false];
    $service->secret = 'existing-secret';
    $service->save();

    SSH::fake('Active: active');

    $this->deleteJson(route('services.networking.secret.destroy', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->secret)->toBeNull();
    expect($service->status)->toEqual(ServiceStatus::READY);

    SSH::assertExecutedContains('sudo install -o redis -g redis -m 600 /dev/null /etc/redis/vito-networking.conf');
    SSH::assertNotExecutedContains('requirepass');
    SSH::assertExecutedContains('sudo systemctl restart redis-server');
});

test('cannot remove the password while networking is open', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->type_data = ['networking' => true];
    $service->secret = 'existing-secret';
    $service->save();

    SSH::fake();

    $this->deleteJson(route('services.networking.secret.destroy', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertStatus(422);

    $service->type_data = ['networking' => false, 'networking_effective' => true];
    $service->save();

    $this->deleteJson(route('services.networking.secret.destroy', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertStatus(422);

    expect($service->refresh()->secret)->toEqual('existing-secret');
    SSH::assertNotExecutedContains('vito-networking.conf');
});

test('cannot manage the password without one or on unsupported services', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');

    SSH::fake();

    $this->postJson(route('services.networking.secret.regenerate', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertStatus(422);

    $nginx = $this->server->services()->where('name', 'nginx')->firstOrFail();

    $this->postJson(route('services.networking.secret.regenerate', [
        'server' => $this->server,
        'service' => $nginx->id,
    ]))->assertStatus(422);

    $this->deleteJson(route('services.networking.secret.destroy', [
        'server' => $this->server,
        'service' => $nginx->id,
    ]))->assertStatus(422);
});

test('user role cannot manage the networking password', function () {
    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->secret = 'existing-secret';
    $service->save();

    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    $this->actingAs($this->user);

    SSH::fake();

    $this->postJson(route('services.networking.secret.regenerate', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertForbidden();

    $this->deleteJson(route('services.networking.secret.destroy', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertForbidden();
});

test('failed password write restores the previous password', function () {
    $this->actingAs($this->user);

    $service = vitoPestFeatureServiceNetworkingTestMemoryDatabase('redis');
    $service->type_data = ['networking' => true, 'networking_effective' => true];
    $service->secret = 'existing-secret';
    $service->save();

    SSH::swap(new class extends SSHFake
    {
        public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null, int $timeout = 0): string
        {
            if (str((string) $command)->contains('requirepass')) {
                throw new SSHCommandError('Connection failed');
            }

            return parent::exec($command, $log, $siteId, $stream, $streamCallback, $timeout);
        }
    });

    $this->postJson(route('services.networking.secret.regenerate', [
        'server' => $this->server,
        'service' => $service->id,
    ]))->assertNoContent();

    $service->refresh();

    expect($service->secret)->toEqual('existing-secret');
    expect($service->status)->toEqual(ServiceStatus::FAILED);

    SSH::assertExecutedContains('sudo cp /etc/redis/vito-networking.conf.vito.bak /etc/redis/vito-networking.conf');
    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'regenerate-networking-secret-failed',
    ]);
});

function vitoPestFeatureServiceNetworkingTestMemoryDatabase(string $name): Service
{
    test()->server->services()->where('type', 'memory_database')->delete();

    return Service::factory()->create([
        'server_id' => test()->server->id,
        'type' => 'memory_database',
        'name' => $name,
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);
}

/**
 * @return array<array<string>>
 */
dataset('memoryDatabases', function () {
    return [
        ['redis'],
        ['valkey'],
    ];
});

/**
 * @return array<array<string>>
 */
dataset('settledStatuses', function () {
    return [
        [ServiceStatus::STOPPED->value],
        [ServiceStatus::DISABLED->value],
        [ServiceStatus::FAILED->value],
    ];
});
