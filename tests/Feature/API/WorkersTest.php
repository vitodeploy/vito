<?php

namespace Tests\Feature\API;

use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\Site;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
