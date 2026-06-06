<?php

namespace Tests\Feature\API;

use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Jobs\Worker\RestartAllJob;
use App\Models\Server;
use App\Models\Site;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkersTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_server_workers_list(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $this->json('GET', route('api.projects.servers.workers', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful();
    }

    public function test_see_site_workers_list(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites.workers', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]))
            ->assertSuccessful();
    }

    public function test_see_server_worker(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('GET', route('api.projects.servers.workers.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSuccessful();
    }

    public function test_see_site_worker(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
            'site_id' => $site->id,
        ]);

        $this->json('GET', route('api.projects.servers.sites.workers.show', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
            'worker' => $worker,
        ]))
            ->assertSuccessful();
    }

    public function test_create_server_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.workers.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'vito',
            'auto_start' => true,
            'auto_restart' => true,
            'numprocs' => 1,
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'status' => WorkerStatus::CREATING,
            ]);

        $this->assertDatabaseHas('workers', [
            'status' => WorkerStatus::RUNNING,
            'name' => 'Test Worker',
        ]);
    }

    public function test_create_site_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->json('POST', route('api.projects.servers.workers.create', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
        ]), [
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'vito',
            'auto_start' => true,
            'auto_restart' => true,
            'numprocs' => 1,
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'status' => WorkerStatus::CREATING,
            ]);

        $this->assertDatabaseHas('workers', [
            'site_id' => $site->id,
            'status' => WorkerStatus::RUNNING,
            'name' => 'Test Worker',
        ]);
    }

    public function test_update_server_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
            'numprocs' => 1,
        ]);

        $this->json('PUT', route('api.projects.servers.workers.update', [
            'project' => $this->server->project,
            'server' => $this->server,
            'worker' => $worker,
        ]), [
            'name' => $worker->name,
            'command' => $worker->command,
            'user' => $worker->user,
            'auto_start' => $worker->auto_start,
            'auto_restart' => $worker->auto_restart,
            'numprocs' => 2,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('workers', [
            'numprocs' => 2,
        ]);
    }

    public function test_update_site_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
            'site_id' => $site->id,
            'numprocs' => 1,
        ]);

        $this->json('PUT', route('api.projects.servers.workers.update', [
            'project' => $this->server->project,
            'server' => $this->server,
            'worker' => $worker,
            'site' => $site,
        ]), [
            'name' => $worker->name,
            'command' => $worker->command,
            'user' => $worker->user,
            'auto_start' => $worker->auto_start,
            'auto_restart' => $worker->auto_restart,
            'numprocs' => 2,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('workers', [
            'site_id' => $site->id,
            'numprocs' => 2,
        ]);
    }

    public function test_start_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('POST', route('api.projects.servers.workers.start', [
            'project' => $this->server->project,
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'status' => WorkerStatus::STARTING,
            ]);
    }

    public function test_restart_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('POST', route('api.projects.servers.workers.restart', [
            'project' => $this->server->project,
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'status' => WorkerStatus::RESTARTING,
            ]);
    }

    public function test_delete_server_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('DELETE', route('api.projects.servers.workers.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }

    public function test_see_worker_logs(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read']);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
        ]);

        $this->json('GET', route('api.projects.servers.workers.logs', [
            'project' => $this->server->project,
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSuccessful()
            ->assertExactJson(['logs' => 'fake output']);
    }

    public function test_delete_site_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
            'site_id' => $site->id,
        ]);

        $this->json('DELETE', route('api.projects.servers.workers.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
            'worker' => $worker,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }

    public function test_cannot_update_site_bootstrap_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
            'site_id' => $site->id,
            'numprocs' => 1,
        ]);

        $site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

        $this->json('PUT', route('api.projects.servers.workers.update', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
            'worker' => $worker,
        ]), [
            'name' => 'renamed',
            'command' => 'renamed command',
            'user' => $worker->user,
            'auto_start' => $worker->auto_start,
            'auto_restart' => $worker->auto_restart,
            'numprocs' => 2,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseMissing('workers', [
            'id' => $worker->id,
            'command' => 'renamed command',
        ]);
    }

    public function test_cannot_delete_site_bootstrap_worker(): void
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server,
            'site_id' => $site->id,
        ]);

        $site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

        $this->json('DELETE', route('api.projects.servers.workers.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $site,
            'worker' => $worker,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertDatabaseHas('workers', ['id' => $worker->id]);
    }

    public function test_resync_workers(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'status' => WorkerStatus::FAILED,
        ]);

        SSH::fake("{$worker->id}:{$worker->id}_00   RUNNING   pid 100, uptime 0:00:05");

        $this->json('POST', route('api.projects.servers.workers.resync', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertExactJson(['synced' => 1]);

        $this->assertDatabaseHas('workers', [
            'id' => $worker->id,
            'status' => WorkerStatus::RUNNING,
        ]);
    }

    public function test_restart_all_workers(): void
    {
        Queue::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        $this->json('POST', route('api.projects.servers.workers.restart-all', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertStatus(202);

        $this->assertDatabaseHas('workers', [
            'id' => $worker->id,
            'status' => WorkerStatus::RESTARTING,
        ]);

        Queue::assertPushed(RestartAllJob::class);
    }

    public function test_cannot_resync_workers_without_write_ability(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $this->json('POST', route('api.projects.servers.workers.resync', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertForbidden();
    }

    public function test_cannot_resync_workers_for_site_on_another_server(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Server $otherServer */
        $otherServer = Server::factory()->create(['user_id' => 1]);

        /** @var Site $otherSite */
        $otherSite = Site::factory()->create([
            'server_id' => $otherServer->id,
        ]);

        $this->json('POST', route('api.projects.servers.workers.resync', [
            'project' => $this->server->project,
            'server' => $this->server,
            'site' => $otherSite,
        ]))
            ->assertNotFound();
    }
}
