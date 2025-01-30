<?php

namespace Tests\Feature\API;

use App\Enums\LoadBalancerMethod;
use App\Enums\SourceControl;
use App\Facades\SSH;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\PrepareLoadBalancer;

class SitesTest extends TestCase
{
    use PrepareLoadBalancer;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepare();
    }

    /**
     * @dataProvider create_data
     */
    public function test_create_site(array $inputs): void
    {
        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], 201),
        ]);

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var \App\Models\SourceControl $sourceControl */
        $sourceControl = \App\Models\SourceControl::factory()->create([
            'provider' => SourceControl::GITHUB,
        ]);

        $inputs['source_control'] = $sourceControl->id;

        $this->json('POST', route('api.projects.servers.sites.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), $inputs)
            ->assertSuccessful()
            ->assertJsonFragment([
                'domain' => $inputs['domain'],
                'aliases' => $inputs['aliases'] ?? [],
                'user' => $inputs['user'] ?? $this->server->getSshUser(),
                'path' => '/home/'.($inputs['user'] ?? $this->server->getSshUser()).'/'.$inputs['domain'],
            ]);
    }

    public function test_see_sites_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'domain' => $site->domain,
            ]);
    }

    public function test_see_site(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'domain' => $site->domain,
            ]);
    }

    public function test_delete_site(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('DELETE', route('api.projects.servers.sites.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }

    public function test_update_load_balancer(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $servers = Server::query()->where('id', '!=', $this->server->id)->get();
        $this->assertEquals(2, $servers->count());

        $this->json('POST', route('api.projects.servers.sites.load-balancer', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $this->site,
        ]), [
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

    public static function create_data(): array
    {
        return \Tests\Feature\SitesTest::create_data();
    }
}
