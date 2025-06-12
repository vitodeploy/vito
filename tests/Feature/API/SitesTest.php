<?php

namespace Tests\Feature\API;

use App\Enums\DeploymentStatus;
use App\Enums\LoadBalancerMethod;
use App\Facades\SSH;
use App\Models\Server;
use App\Models\Site;
use App\Models\SourceControl;
use App\SourceControlProviders\Github;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * @param  array<string, mixed>  $inputs
     */
    #[DataProvider('create_data')]
    public function test_create_site(array $inputs): void
    {
        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
            ], 201),
        ]);

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var SourceControl $sourceControl */
        $sourceControl = SourceControl::factory()->create([
            'provider' => Github::id(),
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

    public function test_update_aliases(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('PUT', route('api.projects.servers.sites.aliases', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]), [
            'aliases' => ['example.com', 'example.net'],
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'aliases' => ['example.com', 'example.net'],
            ]);
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

    public function test_deploy_site(): void
    {
        SSH::fake();

        Http::fake([
            'https://api.github.com/repos/*' => Http::response([
                'commit' => [
                    'sha' => 'abc123',
                    'commit' => [
                        'message' => 'Test commit',
                        'author' => [
                            'name' => 'Test Author',
                            'email' => 'test@example.com',
                            'date' => now()->toIso8601String(),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $script = $site->deploymentScript;
        $script->content = 'git pull';
        $script->save();

        $this->json('POST', route('api.projects.servers.sites.deploy', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertSuccessful()
            ->assertJsonStructure([
                'id',
                'status',
            ]);

        $this->assertDatabaseHas('deployments', [
            'site_id' => $site->id,
            'status' => DeploymentStatus::FINISHED,
        ]);
    }

    public function test_update_deployment_script(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $scriptContent = "git pull\ncomposer install\nphp artisan migrate";

        $this->json('PUT', route('api.projects.servers.sites.deployment-script', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]), [
            'script' => $scriptContent,
        ])
            ->assertSuccessful()
            ->assertNoContent();

        $this->assertDatabaseHas('deployment_scripts', [
            'site_id' => $site->id,
            'content' => $scriptContent,
        ]);
    }

    public function test_update_deployment_script_without_content(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('PUT', route('api.projects.servers.sites.deployment-script', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['script']);
    }

    public function test_show_deployment_script(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $scriptContent = "git pull\ncomposer install";

        $site->deploymentScript->update([
            'content' => $scriptContent,
        ]);

        $this->json('GET', route('api.projects.servers.sites.deployment-script.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertSuccessful()
            ->assertJsonPath('script', $scriptContent);
    }

    public function test_show_env(): void
    {
        $envContent = "APP_NAME=Laravel\nAPP_ENV=production";
        SSH::fake($envContent);

        Sanctum::actingAs($this->user, ['read']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites.env.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'env',
                ],
            ])
            ->assertJsonFragment([
                'env' => $envContent,
            ]);
    }

    public function test_show_env_unauthorized(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, []); // no abilities

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites.env.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertForbidden();
    }

    public function test_update_env(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $envContent = "APP_NAME=Laravel\nAPP_ENV=production";

        $this->json('PUT', route('api.projects.servers.sites.env', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]), [
            'env' => $envContent,
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'domain' => $site->domain,
            ]);
    }

    /**
     * @return array<array<array<string, mixed>>>
     */
    public static function create_data(): array
    {
        return \Tests\Feature\SitesTest::create_data();
    }
}
