<?php

namespace Tests\Feature;

use App\Enums\LoadBalancerMethod;
use App\Facades\SSH;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\PrepareLoadBalancer;

class LoadBalancerTest extends TestCase
{
    use PrepareLoadBalancer;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepare();
    }

    public function test_update_load_balancer_servers(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $servers = Server::query()->where('id', '!=', $this->server->id)->get();
        $this->assertEquals(2, $servers->count());

        $this->post(route('application.update-load-balancer', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'method' => LoadBalancerMethod::ROUND_ROBIN->value,
            'servers' => [
                [
                    'ip' => $servers[0]->local_ip,
                    'port' => 80,
                    'weight' => 1,
                    'backup' => false,
                ],
                [
                    'ip' => $servers[1]->local_ip,
                    'port' => 80,
                    'weight' => 1,
                    'backup' => false,
                ],
            ],
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('load_balancer_servers', [
            'load_balancer_id' => $this->site->id,
            'ip' => $servers[0]->local_ip,
            'port' => 80,
            'weight' => 1,
            'backup' => false,
        ]);
        $this->assertDatabaseHas('load_balancer_servers', [
            'load_balancer_id' => $this->site->id,
            'ip' => $servers[1]->local_ip,
            'port' => 80,
            'weight' => 1,
            'backup' => false,
        ]);
    }
}
