<?php

namespace Tests\Feature\API;

use App\Enums\DeploymentStatus;
use App\Facades\SSH;
use App\Models\Deployment;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\PrepareLoadBalancer;

class DeploymentsTest extends TestCase
{
    use PrepareLoadBalancer;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepare();
    }

    public function test_see_deployments_list(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites.deployments', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertSuccessful();
    }

    public function test_see_deployment(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Deployment $deployment */
        $deployment = Deployment::factory()->create([
            'site_id' => $site->id,
            'deployment_script_id' => $site->deploymentScript?->id,
            'status' => DeploymentStatus::FINISHED,
        ]);

        $this->json('GET', route('api.projects.servers.sites.deployments.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
            'deployment' => $deployment,
        ]))
            ->assertSuccessful();
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
        $site->deploymentScript?->update([
            'content' => 'ls -la',
        ]);

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
}
