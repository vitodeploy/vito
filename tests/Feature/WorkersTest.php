<?php

namespace Tests\Feature;

use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\Server;
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

    public function test_create_worker_with_valid_site_id(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->post(route('workers.store', [
            'server' => $this->server,
        ]), [
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'site_id' => $site->id,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('workers', [
            'server_id' => $this->server->id,
            'site_id' => $site->id,
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'status' => WorkerStatus::RUNNING,
        ]);
    }

    public function test_cannot_create_worker_with_invalid_site_id(): void
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
            'site_id' => 99999, // Non-existent site ID
        ])
            ->assertSessionHasErrors(['site_id']);

        $this->assertDatabaseMissing('workers', [
            'server_id' => $this->server->id,
            'site_id' => 99999,
        ]);
    }

    public function test_cannot_create_worker_with_site_id_from_different_server(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        /** @var Server $otherServer */
        $otherServer = Server::factory()->create(['user_id' => 1]);

        /** @var Site $otherSite */
        $otherSite = Site::factory()->create([
            'server_id' => $otherServer->id,
        ]);

        $this->post(route('workers.store', [
            'server' => $this->server,
        ]), [
            'name' => 'Test Worker',
            'command' => 'php artisan worker:work',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'site_id' => $otherSite->id,
        ])
            ->assertSessionHasErrors(['site_id']);

        $this->assertDatabaseMissing('workers', [
            'server_id' => $this->server->id,
            'site_id' => $otherSite->id,
        ]);
    }

    public function test_edit_worker_with_valid_site_id(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->put(route('workers.update', [
            'server' => $this->server,
            'worker' => $worker,
        ]), [
            'name' => $worker->name,
            'command' => 'updated command',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 2,
            'site_id' => $site->id,
        ])
            ->assertSessionDoesntHaveErrors();

        $worker->refresh();

        $this->assertEquals($site->id, $worker->site_id);
        $this->assertEquals('updated command', $worker->command);
        $this->assertEquals(2, $worker->numprocs);
    }

    public function test_cannot_edit_worker_with_invalid_site_id(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->put(route('workers.update', [
            'server' => $this->server,
            'worker' => $worker,
        ]), [
            'name' => $worker->name,
            'command' => 'updated command',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 2,
            'site_id' => 99999, // Non-existent site ID
        ])
            ->assertSessionHasErrors(['site_id']);

        $worker->refresh();

        $this->assertNotEquals(99999, $worker->site_id);
    }

    public function test_cannot_edit_worker_with_site_id_from_different_server(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var Server $otherServer */
        $otherServer = Server::factory()->create(['user_id' => 1]);

        /** @var Site $otherSite */
        $otherSite = Site::factory()->create([
            'server_id' => $otherServer->id,
        ]);

        $this->put(route('workers.update', [
            'server' => $this->server,
            'worker' => $worker,
        ]), [
            'name' => $worker->name,
            'command' => 'updated command',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 2,
            'site_id' => $otherSite->id,
        ])
            ->assertSessionHasErrors(['site_id']);

        $worker->refresh();

        $this->assertNotEquals($otherSite->id, $worker->site_id);
    }
}
