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
}
