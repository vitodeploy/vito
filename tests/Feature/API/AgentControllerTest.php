<?php

namespace Tests\Feature\API;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function agentService(string $secret = 'test-secret'): Service
    {
        return Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'vito-agent',
            'type' => 'monitoring',
            'type_data' => [
                'url' => '',
                'secret' => $secret,
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
    }

    public function test_store_metrics_with_full_payload(): void
    {
        $service = $this->agentService();

        $payload = [
            'load' => 0.09,
            'disk_total' => '76571',
            'disk_free' => '70927',
            'disk_used' => '2471',
            'memory_total' => '7937236',
            'memory_free' => '7008596',
            'memory_used' => '928640',
            'cpu_cores' => 4,
            'cpu_physical_cores' => 4,
            'cpu_usage_percent' => 0.9900990099009901,
            'cpu_per_core_usage_percent' => [0, 0, 0, 0],
            'cpu_steal_percent' => 0,
            'swap_total' => '0',
            'swap_free' => '0',
            'swap_used' => '0',
            'swap_used_percent' => 0,
            'oom_kill_count' => 0,
            'uptime_seconds' => 26759.36,
            'reboot_required' => false,
        ];

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            $payload,
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'load' => 0.09,
            'cpu_cores' => 4,
            'cpu_physical_cores' => 4,
            'cpu_usage_percent' => 0.99,
            'cpu_steal_percent' => 0,
            'swap_total' => 0,
            'swap_used_percent' => 0,
            'oom_kill_count' => 0,
            'uptime_seconds' => 26759.36,
            'reboot_required' => false,
        ]);

        $metric = $this->server->metrics()->latest('id')->first();
        $this->assertNotNull($metric);
        $this->assertEquals([0.0, 0.0, 0.0, 0.0], $metric->cpu_per_core_usage_percent);
    }

    public function test_store_metrics_with_minimal_payload(): void
    {
        $service = $this->agentService();

        $payload = [
            'load' => 0.5,
            'memory_total' => '1000',
            'memory_used' => '500',
            'memory_free' => '500',
            'disk_total' => '100',
            'disk_used' => '50',
            'disk_free' => '50',
        ];

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            $payload,
            ['secret' => 'test-secret']
        )->assertSuccessful();

        $this->assertDatabaseHas('metrics', [
            'server_id' => $this->server->id,
            'load' => 0.5,
            'cpu_cores' => null,
            'cpu_usage_percent' => null,
            'swap_total' => null,
            'oom_kill_count' => null,
            'uptime_seconds' => null,
            'reboot_required' => null,
        ]);
    }

    public function test_invalid_secret_returns_401_without_validating_payload(): void
    {
        $service = $this->agentService('correct-secret');

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            ['anything' => 'goes'],
            ['secret' => 'wrong-secret']
        )->assertStatus(401);

        $this->assertDatabaseCount('metrics', 0);
    }

    public function test_service_without_secret_cannot_be_used_as_agent_endpoint(): void
    {
        $service = Service::factory()->create([
            'server_id' => $this->server->id,
            'name' => 'remote-monitor',
            'type' => 'monitoring',
            'type_data' => [
                'data_retention' => 7,
            ],
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            ['load' => 0.5, 'memory_total' => 1, 'memory_used' => 1, 'memory_free' => 1, 'disk_total' => 1, 'disk_used' => 1, 'disk_free' => 1],
            ['secret' => '']
        )->assertStatus(401);

        $this->assertDatabaseCount('metrics', 0);
    }

    public function test_invalid_payload_returns_422(): void
    {
        $service = $this->agentService();

        $this->json(
            'POST',
            route('api.servers.agent', ['server' => $this->server, 'id' => $service->id]),
            ['load' => 'not-a-number'],
            ['secret' => 'test-secret']
        )->assertStatus(422);
    }
}
