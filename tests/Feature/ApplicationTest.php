<?php

namespace Tests\Feature;

use App\Enums\DeploymentStatus;
use App\Facades\SSH;
use App\Models\GitHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_application()
    {
        $this->actingAs($this->user);

        $this->get(
            route('servers.sites.show', [
                'server' => $this->server,
                'site' => $this->site,
            ])
        )
            ->assertSuccessful()
            ->assertSee($this->site->domain);
    }

    public function test_update_deployment_script()
    {
        $this->actingAs($this->user);

        $this->post(
            route('servers.sites.application.deployment-script', [
                'server' => $this->server,
                'site' => $this->site,
            ]),
            [
                'script' => 'some script',
            ]
        )->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('deployment_scripts', [
            'site_id' => $this->site->id,
            'content' => 'some script',
        ]);
    }

    public function test_deploy(): void
    {
        SSH::fake('fake output');
        Http::fake([
            'github.com/*' => Http::response([
                'sha' => '123',
                'commit' => [
                    'message' => 'test commit message',
                    'name' => 'test commit name',
                    'email' => 'test@example.com',
                    'url' => 'https://github.com/commit-url',
                ],
            ], 200),
        ]);

        $this->site->deploymentScript->update([
            'content' => 'git pull',
        ]);

        $this->actingAs($this->user);

        $response = $this->post(route('servers.sites.application.deploy', [
            'server' => $this->server,
            'site' => $this->site,
        ]))->assertSessionDoesntHaveErrors();

        $response->assertSessionHas('toast.type', 'success');
        $response->assertSessionHas('toast.message', 'Deployment started!');

        $this->assertDatabaseHas('deployments', [
            'site_id' => $this->site->id,
            'status' => DeploymentStatus::FINISHED,
        ]);

        SSH::assertExecutedContains('cd /home/vito/'.$this->site->domain);
        SSH::assertExecutedContains('git pull');

        $this->get(route('servers.sites.show', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertSee('test commit message');

        $deployment = $this->site->deployments()->first();

        $this->get(route('servers.sites.application.deployment.log', [
            'server' => $this->server,
            'site' => $this->site,
            'deployment' => $deployment,
        ]))
            ->assertRedirect()
            ->assertSessionHas('content', 'fake output');
    }

    public function test_change_branch()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.sites.application.branch', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'branch' => 'master',
        ])
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('toast.type', 'success');

        $this->site->refresh();
        $this->assertEquals('master', $this->site->branch);

        SSH::assertExecutedContains('git checkout -f master');
    }

    public function test_enable_auto_deployment()
    {
        Http::fake([
            'github.com/*' => Http::response([
                'id' => '123',
            ], 200),
        ]);

        $this->actingAs($this->user);

        $this->post(route('servers.sites.application.auto-deployment', [
            'server' => $this->server,
            'site' => $this->site,
        ]))->assertSessionDoesntHaveErrors();

        $this->site->refresh();

        $this->assertTrue($this->site->isAutoDeployment());
    }

    public function test_disable_auto_deployment()
    {
        Http::fake([
            'api.github.com/repos/organization/repository' => Http::response([
                'id' => '123',
            ], 200),
            'api.github.com/repos/organization/repository/hooks/*' => Http::response([], 204),
        ]);

        $this->actingAs($this->user);

        $hook = GitHook::factory()->create([
            'site_id' => $this->site->id,
            'source_control_id' => $this->site->source_control_id,
        ]);

        $this->delete(route('servers.sites.application.auto-deployment', [
            'server' => $this->server,
            'site' => $this->site,
        ]))->assertSessionDoesntHaveErrors();

        $this->site->refresh();

        $this->assertFalse($this->site->isAutoDeployment());
    }

    public function test_update_env_file(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.sites.application.env', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'env' => 'APP_ENV="production"',
        ])->assertSessionDoesntHaveErrors();

        SSH::assertFileUploaded('/home/vito/'.$this->site->domain.'/.env', 'APP_ENV="production"');
    }

    public function test_git_hook_deployment(): void
    {
        SSH::fake();
        Http::fake([
            'github.com/*' => Http::response([
                'sha' => '123',
                'commit' => [
                    'message' => 'test commit message',
                    'name' => 'test commit name',
                    'email' => 'user@example.com',
                    'url' => 'https://github.com',
                ],
            ], 200),
        ]);

        $hook = GitHook::factory()->create([
            'site_id' => $this->site->id,
            'source_control_id' => $this->site->source_control_id,
            'secret' => 'secret',
            'events' => ['push'],
            'actions' => ['deploy'],
        ]);

        $this->site->deploymentScript->update([
            'content' => 'git pull',
        ]);

        $this->post(route('api.git-hooks'), [
            'secret' => 'secret',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('deployments', [
            'site_id' => $this->site->id,
            'deployment_script_id' => $this->site->deploymentScript->id,
            'status' => DeploymentStatus::FINISHED,
        ]);
    }

    public function test_git_hook_deployment_invalid_secret(): void
    {
        SSH::fake();
        Http::fake();

        $hook = GitHook::factory()->create([
            'site_id' => $this->site->id,
            'source_control_id' => $this->site->source_control_id,
            'secret' => 'secret',
            'events' => ['push'],
            'actions' => ['deploy'],
        ]);

        $this->site->deploymentScript->update([
            'content' => 'git pull',
        ]);

        $this->post(route('api.git-hooks'), [
            'secret' => 'invalid-secret',
        ])->assertNotFound();

        $this->assertDatabaseMissing('deployments', [
            'site_id' => $this->site->id,
            'deployment_script_id' => $this->site->deploymentScript->id,
            'status' => DeploymentStatus::FINISHED,
        ]);
    }
}
