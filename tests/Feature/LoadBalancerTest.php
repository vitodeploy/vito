<?php

namespace Tests\Feature;

use App\Actions\Site\UpdateLoadBalancer;
use App\Enums\LoadBalancerMethod;
use App\Facades\SSH;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
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

    public function test_updates_load_balancer_method_in_type_data(): void
    {
        $project = Project::factory()->create();
        $server = Server::factory()->create(['project_id' => $project->id]);
        $site = Site::factory()->create([
            'server_id' => $server->id,
            'type' => 'load-balancer',
            'type_data' => ['method' => LoadBalancerMethod::ROUND_ROBIN->value],
        ]);

        $input = [
            'method' => LoadBalancerMethod::LEAST_CONNECTIONS->value,
            'servers' => [
                [
                    'ip' => $server->local_ip,
                    'port' => 80,
                    'weight' => 1,
                    'backup' => false,
                ],
            ],
        ];

        /** @var Site $site */
        $site = \Mockery::mock($site)->makePartial();
        $site->shouldReceive('webserver->updateVHost')->andReturn();

        app(UpdateLoadBalancer::class)->update($site, $input);

        $site->refresh();
        $this->assertEquals(LoadBalancerMethod::LEAST_CONNECTIONS->value, $site->type_data['method']);
    }

    public function test_creates_load_balancer_servers(): void
    {
        $project = Project::factory()->create();
        $server = Server::factory()->create(['project_id' => $project->id]);
        $site = Site::factory()->create([
            'server_id' => $server->id,
            'type' => 'load-balancer',
            'type_data' => ['method' => LoadBalancerMethod::ROUND_ROBIN->value],
        ]);

        $input = [
            'method' => LoadBalancerMethod::ROUND_ROBIN->value,
            'servers' => [
                [
                    'ip' => $server->local_ip,
                    'port' => 80,
                    'weight' => 1,
                    'backup' => false,
                ],
                [
                    'ip' => $server->local_ip,
                    'port' => 8080,
                    'weight' => 2,
                    'backup' => true,
                ],
            ],
        ];

        /** @var Site $site */
        $site = \Mockery::mock($site)->makePartial();
        $site->shouldReceive('webserver->updateVHost')->andReturn();

        app(UpdateLoadBalancer::class)->update($site, $input);

        $this->assertCount(2, $site->loadBalancerServers);

        $firstServer = $site->loadBalancerServers->first();
        $this->assertEquals($server->local_ip, $firstServer->ip);
        $this->assertEquals(80, $firstServer->port);
        $this->assertEquals(1, $firstServer->weight);
        $this->assertFalse($firstServer->backup);

        $secondServer = $site->loadBalancerServers->last();
        $this->assertEquals($server->local_ip, $secondServer->ip);
        $this->assertEquals(8080, $secondServer->port);
        $this->assertEquals(2, $secondServer->weight);
        $this->assertTrue($secondServer->backup);
    }

    public function test_deletes_existing_load_balancer_servers_before_creating_new_ones(): void
    {
        $project = Project::factory()->create();
        $server = Server::factory()->create(['project_id' => $project->id]);
        $site = Site::factory()->create([
            'server_id' => $server->id,
            'type' => 'load-balancer',
            'type_data' => ['method' => LoadBalancerMethod::ROUND_ROBIN->value],
        ]);

        // Create existing load balancer servers
        $site->loadBalancerServers()->create([
            'ip' => '192.168.1.1',
            'port' => 80,
            'weight' => 1,
            'backup' => false,
        ]);

        $input = [
            'method' => LoadBalancerMethod::ROUND_ROBIN->value,
            'servers' => [
                [
                    'ip' => $server->local_ip,
                    'port' => 80,
                    'weight' => 1,
                    'backup' => false,
                ],
            ],
        ];

        /** @var Site $site */
        $site = \Mockery::mock($site)->makePartial();
        $site->shouldReceive('webserver->updateVHost')->andReturn();

        app(UpdateLoadBalancer::class)->update($site, $input);

        $this->assertCount(1, $site->loadBalancerServers);
        $this->assertEquals($server->local_ip, $site->loadBalancerServers->first()->ip);
    }
}
