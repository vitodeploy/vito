<?php

namespace Tests\Unit\Jobs;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Service\UpdateVitoAgentConfigJob;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateVitoAgentConfigJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_config_and_restarts_agent(): void
    {
        SSH::fake();

        $agent = $this->createAgent(ServiceStatus::READY);

        dispatch(new UpdateVitoAgentConfigJob($this->server));

        SSH::assertExecutedContains('/etc/vito-agent/config.json');
        SSH::assertExecutedContains('&& rm -f /tmp/');
        SSH::assertExecutedContains('chmod 600 /etc/vito-agent/config.json');
        SSH::assertExecutedContains('restart vito-agent');

        $config = json_decode(SSH::getUploadedContent(), true);
        $this->assertSame('https://vito.test/agent-endpoint', $config['url']);
        $this->assertSame('agent-secret', $config['secret']);
        $this->assertArrayNotHasKey('data_retention', $config);

        $units = array_column($config['services'], 'unit', 'id');
        $this->assertCount(6, $units);
        $this->assertContains('nginx', $units);
        $this->assertContains('mysql', $units);
        $this->assertContains('php8.2-fpm', $units);
        $this->assertContains('ufw', $units);
        $this->assertContains('supervisor', $units);
        $this->assertContains('redis-server', $units);
        $this->assertArrayNotHasKey($agent->id, $units);
    }

    public function test_does_nothing_without_vito_agent(): void
    {
        SSH::fake();

        dispatch(new UpdateVitoAgentConfigJob($this->server));

        SSH::assertNotExecutedContains('vito-agent');
        $this->assertSame('', SSH::getUploadedContent());
    }

    public function test_does_nothing_when_agent_is_not_ready(): void
    {
        SSH::fake();

        $this->createAgent(ServiceStatus::STOPPED);

        dispatch(new UpdateVitoAgentConfigJob($this->server));

        SSH::assertNotExecutedContains('restart vito-agent');
        $this->assertSame('', SSH::getUploadedContent());
    }

    private function createAgent(ServiceStatus $status): Service
    {
        /** @var Service $service */
        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'type_data' => [
                'url' => 'https://vito.test/agent-endpoint',
                'secret' => 'agent-secret',
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => $status,
        ]);

        return $service;
    }
}
