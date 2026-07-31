<?php

use App\Actions\Site\UpdateLoadBalancer;
use App\Enums\LoadBalancerMethod;
use App\Facades\SSH;
use App\Models\Project;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\PrepareLoadBalancer;

uses(PrepareLoadBalancer::class);

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->prepare();
});

test('update load balancer servers', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $servers = Server::query()->where('id', '!=', $this->server->id)->get();
    expect($servers->count())->toEqual(2);

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
});

test('updates load balancer method in type data', function () {
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
    $site = Mockery::mock($site)->makePartial();
    $site->shouldReceive('webserver->updateVHost')->andReturn();

    app(UpdateLoadBalancer::class)->update($site, $input);

    $site->refresh();
    expect($site->type_data['method'])->toEqual(LoadBalancerMethod::LEAST_CONNECTIONS->value);
});

test('creates load balancer servers', function () {
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
    $site = Mockery::mock($site)->makePartial();
    $site->shouldReceive('webserver->updateVHost')->andReturn();

    app(UpdateLoadBalancer::class)->update($site, $input);

    expect($site->loadBalancerServers)->toHaveCount(2);

    $firstServer = $site->loadBalancerServers->first();
    expect($firstServer->ip)->toEqual($server->local_ip);
    expect($firstServer->port)->toEqual(80);
    expect($firstServer->weight)->toEqual(1);
    expect($firstServer->backup)->toBeFalse();

    $secondServer = $site->loadBalancerServers->last();
    expect($secondServer->ip)->toEqual($server->local_ip);
    expect($secondServer->port)->toEqual(8080);
    expect($secondServer->weight)->toEqual(2);
    expect($secondServer->backup)->toBeTrue();
});

test('deletes existing load balancer servers before creating new ones', function () {
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
    $site = Mockery::mock($site)->makePartial();
    $site->shouldReceive('webserver->updateVHost')->andReturn();

    app(UpdateLoadBalancer::class)->update($site, $input);

    expect($site->loadBalancerServers)->toHaveCount(1);
    expect($site->loadBalancerServers->first()->ip)->toEqual($server->local_ip);
});
