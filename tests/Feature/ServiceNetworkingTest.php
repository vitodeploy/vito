<?php

namespace Tests\Feature;

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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceNetworkingTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('memoryDatabases')]
    public function test_enable_networking(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase($name);

        SSH::fake("Active: active\nbind 0.0.0.0");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertTrue($service->type_data['networking']);
        $this->assertEquals(ServiceStatus::READY, $service->status);
        $this->assertNotNull($service->secret);

        SSH::assertExecutedContains("sudo test -f /etc/{$name}/{$name}.conf && sudo cp /etc/{$name}/{$name}.conf /etc/{$name}/{$name}.conf.vito.bak");
        SSH::assertNotExecutedContains("[ -f /etc/{$name}/", 'Guards must run under sudo — the conf directory is not readable by the SSH user.');
        SSH::assertExecutedContains('bind[[:space:]]+)(0\.0\.0\.0|\*).*$/\1127.0.0.1/');
        SSH::assertExecutedContains("sudo cp /etc/{$name}/vito-networking.conf /etc/{$name}/vito-networking.conf.vito.bak");
        SSH::assertExecutedContains("sudo install -o {$name} -g {$name} -m 600 /dev/null /etc/{$name}/vito-networking.conf");
        SSH::assertExecutedContains('printf \'requirepass "%s"\n\' "$VITO_MEMDB_PASSWORD"');
        SSH::assertExecutedContains('sudo sed -i \'/^# BEGIN VITO NETWORKING$/,/^# END VITO NETWORKING$/d\'');
        SSH::assertExecutedContains('# BEGIN VITO NETWORKING\nbind 0.0.0.0\ninclude %s\n# END VITO NETWORKING\n');
        SSH::assertExecutedContains("sudo systemctl restart {$name}-server");
        SSH::assertNotExecutedContains((string) $service->secret, 'The networking password must never appear in a command.');
    }

    #[DataProvider('memoryDatabases')]
    public function test_disable_networking(string $name): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase($name);
        $service->type_data = ['networking' => true];
        $service->secret = 'existing-secret';
        $service->save();

        SSH::fake("Active: active\nbind 127.0.0.1");

        $this->postJson(route('services.networking.disable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertFalse($service->type_data['networking']);
        $this->assertEquals('existing-secret', $service->secret);
        $this->assertEquals(ServiceStatus::READY, $service->status);

        SSH::assertExecutedContains('# BEGIN VITO NETWORKING\nbind 127.0.0.1\ninclude %s\n# END VITO NETWORKING\n');
        SSH::assertExecutedContains("sudo test -f /etc/{$name}/vito-networking.conf || sudo install -o {$name}");
        SSH::assertExecutedContains("sudo systemctl restart {$name}-server");
        SSH::assertNotExecutedContains('bind 0.0.0.0');
        SSH::assertNotExecutedContains("[ -f /etc/{$name}/", 'An unprivileged guard would truncate the include file and drop the password.');
    }

    public function test_enable_persists_the_secret_before_dispatching_the_job(): void
    {
        Queue::fake();
        SSH::fake();

        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        Queue::assertPushed(ToggleNetworkingJob::class);
        $this->assertEquals(ServiceStatus::RESTARTING, $service->status);
        $this->assertEquals(32, strlen((string) $service->secret));

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
    }

    public function test_enable_keeps_the_existing_secret(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');
        $service->secret = 'existing-secret';
        $service->save();

        SSH::fake("Active: active\nbind 0.0.0.0");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $this->assertEquals('existing-secret', $service->refresh()->secret);
    }

    public function test_secret_is_encrypted_at_rest_and_never_exposed_by_the_resource(): void
    {
        $service = $this->memoryDatabase('redis');
        $service->secret = 'plain-text-secret';
        $service->type_data = ['networking' => true];
        $service->save();
        $service->refresh();

        $this->assertNotEquals('plain-text-secret', $service->getRawOriginal('secret'));
        $this->assertEquals('plain-text-secret', $service->secret);

        $payload = (new ServiceResource($service))->toArray(request());

        $this->assertArrayNotHasKey('secret', $payload);
        $this->assertStringNotContainsString('plain-text-secret', (string) json_encode($payload));
        $this->assertTrue($payload['supports_networking']);
        $this->assertTrue($payload['networking_enabled']);
    }

    public function test_resource_does_not_support_networking_for_other_services(): void
    {
        $service = $this->server->services()->where('name', 'nginx')->firstOrFail();

        $payload = (new ServiceResource($service))->toArray(request());

        $this->assertFalse($payload['supports_networking']);
        $this->assertFalse($payload['networking_enabled']);
    }

    #[DataProvider('settledStatuses')]
    public function test_enable_networking_skips_the_restart_when_the_service_is_not_running(string $status): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');
        $service->status = ServiceStatus::from($status);
        $service->save();

        SSH::fake();

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertTrue($service->type_data['networking']);
        $this->assertEquals(ServiceStatus::from($status), $service->status);

        SSH::assertExecutedContains('# BEGIN VITO NETWORKING');
        SSH::assertNotExecutedContains('systemctl restart');
        SSH::assertNotExecutedContains('grep -E');
    }

    public function test_cannot_toggle_networking_while_the_status_is_not_settled(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');
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

        $this->assertNull($service->refresh()->type_data);
    }

    public function test_unsupported_service_cannot_toggle_networking(): void
    {
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
    }

    public function test_service_without_a_handler_does_not_break_networking_or_the_index(): void
    {
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
    }

    public function test_user_role_cannot_manage_networking(): void
    {
        $service = $this->memoryDatabase('redis');

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
    }

    public function test_networking_details_report_the_effective_host_state(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');

        SSH::fake('bind 0.0.0.0');

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
                'port' => 6379,
            ]);

        SSH::assertExecutedContains("sudo grep -E '^[[:space:]]*bind' /etc/redis/redis.conf | tail -1 || true");
    }

    public function test_networking_details_report_no_effective_state_when_the_host_is_unreachable(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');

        SSH::swap(new class extends SSHFake
        {
            public function exec(string|View $command, string $log = '', ?int $siteId = null, ?bool $stream = false, ?callable $streamCallback = null, int $timeout = 0): string
            {
                throw new SSHCommandError('Connection failed');
            }
        });

        $this->getJson(route('services.networking', [
            'server' => $this->server,
            'service' => $service->id,
        ]))
            ->assertOk()
            ->assertJson([
                'supported' => true,
                'pending' => false,
                'enabled' => false,
                'effective' => null,
                'error' => true,
                'port' => 6379,
            ]);
    }

    public function test_failed_enable_rolls_back_and_marks_the_service_as_failed(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');

        SSH::fake("Active: active\nbind 127.0.0.1");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
        $this->assertFalse($service->type_data['networking']);
        $this->assertTrue($service->type_data['networking_failed']);

        SSH::assertExecutedContains('sudo cp /etc/redis/redis.conf.vito.bak /etc/redis/redis.conf');
        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'enable-networking-failed',
        ]);
    }

    public function test_failed_config_write_rolls_back_and_marks_the_service_as_failed(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');

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

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
        $this->assertFalse($service->type_data['networking']);
        $this->assertTrue($service->type_data['networking_failed']);

        SSH::assertExecutedContains('sudo cp /etc/redis/redis.conf.vito.bak /etc/redis/redis.conf');
        SSH::assertExecutedContains('sudo cp /etc/redis/vito-networking.conf.vito.bak /etc/redis/vito-networking.conf');
        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'enable-networking-failed',
        ]);
    }

    public function test_failed_rollback_is_logged(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');

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

        $this->assertEquals(ServiceStatus::FAILED, $service->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'rollback-redis-networking-failed',
        ]);
        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'enable-networking-failed',
        ]);
    }

    public function test_networking_details_report_management_and_password_capability(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');

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
    }

    public function test_a_successful_toggle_clears_a_previous_networking_failure(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');
        $service->type_data = ['networking' => false, 'networking_failed' => true];
        $service->save();

        SSH::fake("Active: active\nbind 0.0.0.0");

        $this->postJson(route('services.networking.enable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertTrue($service->type_data['networking']);
        $this->assertArrayNotHasKey('networking_failed', $service->type_data);
    }

    public function test_failed_disable_does_not_roll_back(): void
    {
        $this->actingAs($this->user);

        $service = $this->memoryDatabase('redis');
        $service->type_data = ['networking' => true];
        $service->secret = 'existing-secret';
        $service->save();

        SSH::fake("Active: active\nbind 0.0.0.0");

        $this->postJson(route('services.networking.disable', [
            'server' => $this->server,
            'service' => $service->id,
        ]))->assertNoContent();

        $service->refresh();

        $this->assertEquals(ServiceStatus::FAILED, $service->status);
        $this->assertTrue($service->type_data['networking']);

        SSH::assertNotExecutedContains('sudo cp /etc/redis/redis.conf.vito.bak /etc/redis/redis.conf');
    }

    private function memoryDatabase(string $name): Service
    {
        $this->server->services()->where('type', 'memory_database')->delete();

        return Service::factory()->create([
            'server_id' => $this->server->id,
            'type' => 'memory_database',
            'name' => $name,
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    /**
     * @return array<array<string>>
     */
    public static function memoryDatabases(): array
    {
        return [
            ['redis'],
            ['valkey'],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function settledStatuses(): array
    {
        return [
            [ServiceStatus::STOPPED->value],
            [ServiceStatus::DISABLED->value],
            [ServiceStatus::FAILED->value],
        ];
    }
}
