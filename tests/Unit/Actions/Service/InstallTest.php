<?php

namespace Tests\Unit\Actions\Service;

use App\Actions\Service\Install;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_vito_agent(): void
    {
        SSH::fake('Active: active');
        Http::fake([
            'https://api.github.com/repos/vitodeploy/agent/tags' => Http::response([['name' => '0.1.0']]),
        ]);

        $service = app(Install::class)->install($this->server, [
            'type' => 'monitoring',
            'name' => 'vito-agent',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'version' => '0.1.0',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertNotNull($service->type_data);
    }

    public function test_install_vito_agent_failed(): void
    {
        $this->expectExceptionMessage('Failed to fetch tags');
        SSH::fake('Active: inactive');
        Http::fake([
            'https://api.github.com/repos/vitodeploy/agent/tags' => Http::response([]),
        ]);
        app(Install::class)->install($this->server, [
            'type' => 'monitoring',
            'name' => 'vito-agent',
            'version' => 'latest',
        ]);
    }

    public function test_install_nginx(): void
    {
        $this->server->webserver()->delete();

        SSH::fake('Active: active');

        $service = app(Install::class)->install($this->server, [
            'type' => 'webserver',
            'name' => 'nginx',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'nginx',
            'type' => 'webserver',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertNotNull($service->type_data);
    }

    public function test_install_mysql(): void
    {
        $this->server->database()->delete();

        SSH::fake('Active: active');

        $service = app(Install::class)->install($this->server, [
            'type' => 'database',
            'name' => 'mysql',
            'version' => '8.0',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'mysql',
            'type' => 'database',
            'version' => '8.0',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertNotNull($service->type_data);
    }

    public function test_install_mysql_failed(): void
    {
        $this->expectException(ValidationException::class);
        app(Install::class)->install($this->server, [
            'type' => 'database',
            'name' => 'mysql',
            'version' => '8.0',
        ]);
    }

    public function test_install_supervisor(): void
    {
        $this->server->processManager()->delete();

        SSH::fake('Active: active');

        $service = app(Install::class)->install($this->server, [
            'type' => 'process_manager',
            'name' => 'supervisor',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'supervisor',
            'type' => 'process_manager',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertNotNull($service->type_data);
    }

    public function test_install_redis(): void
    {
        $this->server->memoryDatabase()->delete();

        SSH::fake('Active: active');

        $service = app(Install::class)->install($this->server, [
            'type' => 'memory_database',
            'name' => 'redis',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'redis',
            'type' => 'memory_database',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->assertNotNull($service->type_data);
    }
}
