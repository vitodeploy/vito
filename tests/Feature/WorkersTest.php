<?php

namespace Tests\Feature;

use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\Site;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WorkersTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_workers(): void
    {
        $this->actingAs($this->user);

        Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->get(route('workers', [
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('workers/index'));

    }

    public function test_delete_worker(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->delete(route('workers.destroy', [
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('workers', [
            'id' => $worker->id,
        ]);
    }

    public function test_create_worker(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('workers.store', [
            'server' => $this->server,
        ]), [
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('workers', [
            'server_id' => $this->server->id,
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'status' => WorkerStatus::RUNNING,
        ]);
    }

    public function test_create_worker_as_isolated_user(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->site->user = 'example';
        $this->site->save();

        $this->post(route('workers.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'example',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('workers', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'command' => 'php artisan worker:work',
            'user' => 'example',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'status' => WorkerStatus::RUNNING,
        ]);
    }

    public function test_cannot_create_worker_as_invalid_user(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('workers.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'example',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
        ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('workers', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'user' => 'example',
        ]);
    }

    public function test_cannot_create_worker_on_another_sites_user(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'example',
        ]);

        $this->post(route('workers.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'example',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
        ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('workers', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'user' => 'example',
        ]);
    }

    public function test_start_worker(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::STOPPED,
        ]);

        $this->post(route('workers.start', [
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('workers', [
            'id' => $worker->id,
            'status' => WorkerStatus::RUNNING,
        ]);
    }

    public function test_stop_worker(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        $this->post(route('workers.stop', [
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('workers', [
            'id' => $worker->id,
            'status' => WorkerStatus::STOPPED,
        ]);
    }

    public function test_restart_worker(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        $this->post(route('workers.restart', [
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('workers', [
            'id' => $worker->id,
            'status' => WorkerStatus::RUNNING,
        ]);
    }

    public function test_show_logs(): void
    {
        SSH::fake('logs');

        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        $this->get(route('workers.logs', [
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSuccessful();
    }
}
