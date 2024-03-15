<?php

namespace Tests\Feature;

use App\Enums\Database;
use App\Enums\OperatingSystem;
use App\Enums\ServerProvider;
use App\Enums\ServerStatus;
use App\Enums\ServerType;
use App\Enums\ServiceStatus;
use App\Enums\Webserver;
use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @TODO add more tests
 */
class ServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_custom_server(): void
    {
        $this->actingAs($this->user);

        SSH::fake('Active: active'); // fake output for service installations

        $this->post(route('servers.create'), [
            'type' => ServerType::REGULAR,
            'provider' => ServerProvider::CUSTOM,
            'name' => 'test',
            'ip' => '1.1.1.1',
            'port' => '22',
            'os' => OperatingSystem::UBUNTU22,
            'webserver' => Webserver::NGINX,
            'database' => Database::MYSQL80,
            'php' => '8.2',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'name' => 'test',
            'ip' => '1.1.1.1',
            'status' => ServerStatus::READY,
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => 1,
            'type' => 'php',
            'version' => '8.2',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => 1,
            'type' => 'webserver',
            'name' => 'nginx',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => 1,
            'type' => 'database',
            'name' => 'mysql',
            'version' => '8.0',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => 1,
            'type' => 'firewall',
            'name' => 'ufw',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_delete_server(): void
    {
        $this->actingAs($this->user);

        SSH::fake();

        $this->delete(route('servers.delete', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('servers', [
            'id' => $this->server->id,
        ]);
    }

    public function test_check_connection_is_ready(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->server->update(['status' => ServerStatus::DISCONNECTED]);

        $this->post(route('servers.settings.check-connection', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::READY,
        ]);
    }

    public function test_connection_failed(): void
    {
        SSH::fake()->connectionWillFail();

        $this->actingAs($this->user);

        $this->server->update(['status' => ServerStatus::READY]);

        $this->post(route('servers.settings.check-connection', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }

    public function test_reboot_server(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.settings.reboot', $this->server))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }

    public function test_edit_server(): void
    {
        $this->actingAs($this->user);

        $this->post(route('servers.settings.edit', $this->server), [
            'name' => 'new-name',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'name' => 'new-name',
        ]);
    }

    public function test_edit_server_ip_address(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.settings.edit', $this->server), [
            'ip' => '2.2.2.2',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'ip' => '2.2.2.2',
            'status' => ServerStatus::READY,
        ]);
    }

    public function test_edit_server_ip_address_and_disconnect(): void
    {
        SSH::fake()->connectionWillFail();

        $this->actingAs($this->user);

        $this->post(route('servers.settings.edit', $this->server), [
            'ip' => '2.2.2.2',
            'port' => 2222,
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'ip' => '2.2.2.2',
            'port' => 2222,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }
}
