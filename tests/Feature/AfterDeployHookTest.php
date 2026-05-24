<?php

namespace Tests\Feature;

use App\Enums\DeploymentStatus;
use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\Deployment;
use App\Models\Site;
use App\Models\Worker;
use App\SiteTypes\NodeSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AfterDeployHookTest extends TestCase
{
    use RefreshDatabase;

    private Site $proxiedSite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proxiedSite = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'isolated-foo',
            'path' => '/home/isolated-foo/app.test',
            'type' => NodeSite::id(),
            'port' => 3000,
            'type_data' => [
                'node_version' => '22',
                'package_manager' => 'npm',
                'start_command' => 'npm start',
            ],
        ]);
    }

    public function test_after_deploy_creates_worker_when_none_exists(): void
    {
        SSH::fake();

        $type = $this->proxiedSite->type();
        $type->afterDeploy(Deployment::factory()->create([
            'site_id' => $this->proxiedSite->id,
            'status' => DeploymentStatus::DEPLOYING,
        ]));

        $worker = $this->proxiedSite->workers()->where('name', 'app')->first();
        $this->assertNotNull($worker);
        $this->assertSame('npm start', $worker->command);

        $this->proxiedSite->refresh();
        $this->assertSame($worker->id, $this->proxiedSite->type_data['bootstrap_worker_id']);
    }

    public function test_after_deploy_is_idempotent_when_worker_already_recorded(): void
    {
        SSH::fake();

        $existing = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->proxiedSite->id,
            'user' => 'isolated-foo',
            'name' => 'app',
            'command' => 'npm start',
            'status' => WorkerStatus::RUNNING,
        ]);
        $this->proxiedSite->jsonUpdate('type_data', 'bootstrap_worker_id', $existing->id);

        $type = $this->proxiedSite->type();
        $type->afterDeploy(Deployment::factory()->create([
            'site_id' => $this->proxiedSite->id,
            'status' => DeploymentStatus::DEPLOYING,
        ]));

        $this->assertSame(1, $this->proxiedSite->workers()->where('name', 'app')->count());
    }

    public function test_after_deploy_backfills_by_known_default_command(): void
    {
        SSH::fake();

        $existing = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->proxiedSite->id,
            'user' => 'isolated-foo',
            'name' => 'app',
            'command' => 'npm start',
            'status' => WorkerStatus::RUNNING,
        ]);

        $type = $this->proxiedSite->type();
        $type->afterDeploy(Deployment::factory()->create([
            'site_id' => $this->proxiedSite->id,
            'status' => DeploymentStatus::DEPLOYING,
        ]));

        $this->proxiedSite->refresh();
        $this->assertSame($existing->id, $this->proxiedSite->type_data['bootstrap_worker_id']);
        $this->assertSame(1, $this->proxiedSite->workers()->where('name', 'app')->count());
    }

    public function test_after_deploy_refuses_to_adopt_user_worker_with_custom_command(): void
    {
        SSH::fake();

        Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->proxiedSite->id,
            'user' => 'isolated-foo',
            'name' => 'app',
            'command' => 'node custom-server.js',
            'status' => WorkerStatus::RUNNING,
        ]);

        // bootstrapWorker() should return null (command doesn't match a known
        // default) so afterDeploy() attempts to create a new worker. Worker
        // name uniqueness then surfaces the conflict — the user must rename
        // their custom worker before deploy can succeed.
        $type = $this->proxiedSite->type();

        $this->expectException(ValidationException::class);

        $type->afterDeploy(Deployment::factory()->create([
            'site_id' => $this->proxiedSite->id,
            'status' => DeploymentStatus::DEPLOYING,
        ]));
    }
}
