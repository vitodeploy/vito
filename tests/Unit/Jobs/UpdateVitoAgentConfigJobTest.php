<?php

namespace Tests\Unit\Jobs;

use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Service\UpdateVitoAgentConfigJob;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateVitoAgentConfigJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_config_and_restarts_agent(): void
    {
        SSH::fake();

        $agent = $this->createAgent(ServiceStatus::READY);
        $this->server->services()->create([
            'type' => 'log_analysis',
            'name' => 'goaccess',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        dispatch(new UpdateVitoAgentConfigJob($this->server));

        SSH::assertExecutedContains('/etc/vito-agent/config.json');
        SSH::assertExecutedContains('&& rm -f /tmp/');
        SSH::assertExecutedContains('chmod 600 /etc/vito-agent/config.json');
        SSH::assertExecutedContains('Monitoring services: ');
        SSH::assertNotExecutedContains('agent-secret');
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
        $this->assertNotContains('', $units);
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

    public function test_dispatch_for_never_throws_into_the_calling_job(): void
    {
        Queue::fake();

        $this->createAgent(ServiceStatus::READY);
        config()->set('service.services.broken.handler', 'App\Services\DoesNotExist');

        /** @var Service $broken */
        $broken = $this->server->services()->create([
            'type' => 'broken',
            'name' => 'broken',
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        UpdateVitoAgentConfigJob::dispatchFor($broken);

        Queue::assertNotPushed(UpdateVitoAgentConfigJob::class);
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
