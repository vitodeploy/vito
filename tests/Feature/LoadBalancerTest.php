<?php

namespace Tests\Feature;

use App\Enums\LoadBalancerMethod;
use App\Facades\SSH;
use App\Models\Server;
use App\Web\Pages\Servers\Sites\View;
use App\Web\Pages\Servers\Sites\Widgets\LoadBalancerServers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_visit_load_balancer_servers(): void
    {
        $this->actingAs($this->user);

        $this->get(
            View::getUrl([
                'server' => $this->server,
                'site' => $this->site,
            ])
        )
            ->assertSuccessful()
            ->assertSee($this->site->domain)
            ->assertSee('Load Balancer Servers');
    }

    public function test_update_load_balancer_servers()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $servers = Server::query()->where('id', '!=', $this->server->id)->get();
        $this->assertEquals(2, $servers->count());

        Livewire::test(LoadBalancerServers::class, [
            'site' => $this->site,
        ])
            ->assertFormExists()
            ->fillForm([
                'method' => LoadBalancerMethod::ROUND_ROBIN,
                'servers' => [
                    [
                        'server' => $servers[0]->local_ip,
                        'port' => 80,
                        'weight' => 1,
                        'backup' => false,
                    ],
                    [
                        'server' => $servers[1]->local_ip,
                        'port' => 80,
                        'weight' => 1,
                        'backup' => false,
                    ],
                ],
            ])
            ->call('save')
            ->assertSuccessful();

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
