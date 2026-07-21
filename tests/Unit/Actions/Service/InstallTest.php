<?php

namespace Tests\Unit\Actions\Service;

use App\Actions\Service\Install;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Service\UpdateVitoAgentConfigJob;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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

        $this->server->monitoring()?->delete();

        app(Install::class)->install($this->server, [
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
    }

    public function test_install_vito_agent_failed(): void
    {
        $this->server->monitoring()?->delete();
        SSH::fake('Active: inactive');
        Http::fake([
            'https://api.github.com/repos/vitodeploy/agent/tags' => Http::response([]),
        ]);

        $service = app(Install::class)->install($this->server, [
            'type' => 'monitoring',
            'name' => 'vito-agent',
            'version' => 'latest',
        ]);

        // Wait for the job to complete and check the service status
        $service->refresh();
        $this->assertEquals(ServiceStatus::INSTALLATION_FAILED, $service->status);
    }

    public function test_install_nginx(): void
    {
        $this->server->webserver()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
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
    }

    public function test_install_caddy(): void
    {
        $this->server->webserver()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
            'type' => 'webserver',
            'name' => 'caddy',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'caddy',
            'type' => 'webserver',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_install_mysql(): void
    {
        $this->server->database()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
            'type' => 'database',
            'name' => 'mysql',
            'version' => '8.4',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'mysql',
            'type' => 'database',
            'version' => '8.4',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_install_mysql_failed(): void
    {
        $this->expectException(ValidationException::class);
        app(Install::class)->install($this->server, [
            'type' => 'database',
            'name' => 'mysql',
            'version' => '8.4',
        ]);
    }

    public function test_install_supervisor(): void
    {
        $this->server->processManager()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
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
    }

    public function test_install_redis(): void
    {
        $this->server->memoryDatabase()->delete();

        SSH::fake('Active: active');

        app(Install::class)->install($this->server, [
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
    }

    public function test_installing_service_dispatches_agent_config_update(): void
    {
        SSH::fake('Active: active');
        Bus::fake([UpdateVitoAgentConfigJob::class]);

        $this->createVitoAgent();
        $this->server->memoryDatabase()->delete();

        app(Install::class)->install($this->server, [
            'type' => 'memory_database',
            'name' => 'redis',
            'version' => 'latest',
        ]);

        Bus::assertDispatched(UpdateVitoAgentConfigJob::class);
    }

    public function test_installing_service_without_unit_does_not_dispatch_agent_config_update(): void
    {
        SSH::fake('Active: active');
        Bus::fake([UpdateVitoAgentConfigJob::class]);

        $this->createVitoAgent();
        $this->server->services()->where('name', 'nodejs')->delete();

        app(Install::class)->install($this->server, [
            'type' => 'nodejs',
            'name' => 'nodejs',
            'version' => '20',
        ]);

        Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
    }

    public function test_failed_install_does_not_dispatch_agent_config_update(): void
    {
        SSH::fake('inactive');
        Bus::fake([UpdateVitoAgentConfigJob::class]);

        $this->createVitoAgent();
        $this->server->memoryDatabase()->delete();

        app(Install::class)->install($this->server, [
            'type' => 'memory_database',
            'name' => 'redis',
            'version' => 'latest',
        ]);

        $this->assertDatabaseHas('services', [
            'server_id' => $this->server->id,
            'name' => 'redis',
            'status' => ServiceStatus::INSTALLATION_FAILED,
        ]);
        Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
    }

    public function test_installing_service_without_vito_agent_does_not_dispatch_agent_config_update(): void
    {
        SSH::fake('Active: active');
        Bus::fake([UpdateVitoAgentConfigJob::class]);

        $this->server->memoryDatabase()->delete();

        app(Install::class)->install($this->server, [
            'type' => 'memory_database',
            'name' => 'redis',
            'version' => 'latest',
        ]);

        Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
    }

    public function test_installing_vito_agent_writes_config_with_services(): void
    {
        SSH::fake('Active: active');
        Bus::fake([UpdateVitoAgentConfigJob::class]);
        Http::fake([
            'https://api.github.com/repos/vitodeploy/agent/tags' => Http::response([['name' => '0.1.0']]),
        ]);

        $this->server->monitoring()?->delete();

        app(Install::class)->install($this->server, [
            'type' => 'monitoring',
            'name' => 'vito-agent',
            'version' => 'latest',
        ]);

        $config = json_decode(SSH::getUploadedContent(), true);
        $this->assertNotEmpty($config['url']);
        $this->assertNotEmpty($config['secret']);
        $this->assertArrayNotHasKey('data_retention', $config);
        $this->assertContains('nginx', array_column($config['services'], 'unit'));

        Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
    }

    private function createVitoAgent(): void
    {
        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'type_data' => [
                'url' => 'https://vito.test/agent-endpoint',
                'secret' => 'agent-secret',
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }
}
