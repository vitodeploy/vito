<?php

namespace Tests\Feature;

use App\Enums\DeploymentStatus;
use App\Facades\SSH;
use App\Models\GitHook;
use App\Notifications\DeploymentCompleted;
use App\Web\Pages\Servers\Sites\View;
use App\Web\Pages\Servers\Sites\Widgets\DeploymentsList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_visit_application()
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
            ->assertSee('Deployments')
            ->assertSee('Actions');
    }

    public function test_update_deployment_script()
    {
        $this->actingAs($this->user);

        Livewire::test(View::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('deployment-script', [
                'script' => 'some script',
            ])
            ->assertSuccessful();

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
            ]),
        ]);
        Notification::fake();

        $this->site->deploymentScript->update([
            'content' => 'git pull',
        ]);

        $this->actingAs($this->user);

        Livewire::test(View::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('deploy')
            ->assertSuccessful()
            ->assertNotified('Deployment started!');

        $this->assertDatabaseHas('deployments', [
            'site_id' => $this->site->id,
            'status' => DeploymentStatus::FINISHED,
        ]);

        SSH::assertExecutedContains('cd /home/vito/'.$this->site->domain);
        SSH::assertExecutedContains('git pull');

        Notification::assertSentTo($this->notificationChannel, DeploymentCompleted::class);

        $this->get(
            View::getUrl([
                'server' => $this->server,
                'site' => $this->site,
            ])
        )
            ->assertSuccessful()
            ->assertSee('test commit message');

        $deployment = $this->site->deployments()->first();

        Livewire::test(DeploymentsList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('view', $deployment->id)
            ->assertSuccessful();
    }

    public function test_change_branch()
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(View::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('branch', [
                'branch' => 'master',
            ])
            ->assertSuccessful()
            ->assertNotified('Branch updated!');

        $this->site->refresh();
        $this->assertEquals('master', $this->site->branch);

        SSH::assertExecutedContains('git checkout -f master');
    }

    public function test_enable_auto_deployment()
    {
        Http::fake([
            'github.com/*' => Http::response([
                'id' => '123',
            ], 201),
        ]);

        $this->actingAs($this->user);

        Livewire::test(View::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('auto-deployment')
            ->assertSuccessful()
            ->assertNotified('Auto deployment enabled!');

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

        GitHook::factory()->create([
            'site_id' => $this->site->id,
            'source_control_id' => $this->site->source_control_id,
        ]);

        Livewire::test(View::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('auto-deployment')
            ->assertSuccessful()
            ->assertNotified('Auto deployment disabled!');

        $this->site->refresh();

        $this->assertFalse($this->site->isAutoDeployment());
    }

    public function test_update_env_file(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(View::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('dot-env', [
                'env' => 'APP_ENV="production"',
            ])
            ->assertSuccessful()
            ->assertNotified('.env updated!');

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

        GitHook::factory()->create([
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

        GitHook::factory()->create([
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
