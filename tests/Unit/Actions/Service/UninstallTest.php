<?php

namespace Tests\Unit\Actions\Service;

use App\Actions\Network\CreateNetwork;
use App\Actions\Service\Uninstall;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Service\UpdateVitoAgentConfigJob;
use App\Models\Database;
use App\Models\Service;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UninstallTest extends TestCase
{
    use RefreshDatabase;

    public function test_uninstall_vito_agent(): void
    {
        SSH::fake();

        $this->server->monitoring()?->delete();

        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        app(Uninstall::class)->uninstall($this->server->monitoring());

        $this->assertDatabaseMissing('services', [
            'id' => $service->id,
        ]);
    }

    public function test_uninstalling_service_dispatches_agent_config_update(): void
    {
        SSH::fake();
        Bus::fake([UpdateVitoAgentConfigJob::class]);

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

        $redis = $this->server->services()->where('name', 'redis')->firstOrFail();

        app(Uninstall::class)->uninstall($redis);

        $this->assertDatabaseMissing('services', ['id' => $redis->id]);
        Bus::assertDispatched(UpdateVitoAgentConfigJob::class);
    }

    public function test_uninstalling_vito_agent_does_not_dispatch_agent_config_update(): void
    {
        SSH::fake();
        Bus::fake([UpdateVitoAgentConfigJob::class]);

        $this->server->monitoring()?->delete();

        Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        app(Uninstall::class)->uninstall($this->server->monitoring());

        Bus::assertNotDispatched(UpdateVitoAgentConfigJob::class);
    }

    /**
     * Cannot uninstall nginx because some sites using it
     */
    public function test_cannot_uninstall_nginx(): void
    {
        SSH::fake();

        $this->expectException(ValidationException::class);

        app(Uninstall::class)->uninstall($this->server->webserver());
    }

    /**
     * Cannot uninstall caddy because some sites using it
     */
    public function test_cannot_uninstall_caddy(): void
    {
        SSH::fake();

        $this->expectException(ValidationException::class);

        app(Uninstall::class)->uninstall($this->server->webserver());
    }

    /**
     * Cannot uninstall mysql because some databases exist
     */
    public function test_cannot_uninstall_mysql(): void
    {
        SSH::fake();

        Database::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->expectException(ValidationException::class);

        app(Uninstall::class)->uninstall($this->server->database());
    }

    public function test_cannot_uninstall_wireguard_when_server_is_network_member(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        $this->expectException(ValidationException::class);

        app(Uninstall::class)->uninstall($this->server->service('vpn'));
    }

    public function test_can_uninstall_wireguard_when_server_is_not_a_network_member(): void
    {
        SSH::fake();

        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'wireguard',
            'type' => 'vpn',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        app(Uninstall::class)->uninstall($service);

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    /**
     * Cannot uninstall supervisor because some queues exist
     */
    public function test_cannot_uninstall_supervisor(): void
    {
        SSH::fake();

        Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->expectException(ValidationException::class);

        app(Uninstall::class)->uninstall($this->server->processManager());
    }
}
