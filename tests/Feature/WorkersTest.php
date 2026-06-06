<?php

namespace Tests\Feature;

use App\Actions\Worker\ManageWorker;
use App\Enums\UserRole;
use App\Enums\WorkerStatus;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Jobs\Worker\ManageJob;
use App\Jobs\Worker\RestartAllJob;
use App\Models\Server;
use App\Models\Site;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

    public function test_start_worker_reloads_config_before_starting(): void
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

        SSH::assertExecutedContains('supervisorctl reread');
        SSH::assertExecutedContains("supervisorctl update {$worker->id}");
        SSH::assertExecutedContains("supervisorctl start {$worker->id}:*");
    }

    public function test_start_and_restart_clear_error_optimistically(): void
    {
        Queue::fake();

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::FAILED,
            'error' => '10:10_00: ERROR (no such file)',
        ]);

        app(ManageWorker::class)->restart($worker);

        $worker->refresh();
        $this->assertSame(WorkerStatus::RESTARTING, $worker->status);
        $this->assertNull($worker->error);

        $worker->status = WorkerStatus::FAILED;
        $worker->error = 'boom';
        $worker->save();

        app(ManageWorker::class)->start($worker);

        $worker->refresh();
        $this->assertSame(WorkerStatus::STARTING, $worker->status);
        $this->assertNull($worker->error);

        Queue::assertPushed(ManageJob::class, 2);
    }

    public function test_start_worker_clears_previous_error(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::FAILED,
            'error' => '10:10_00: ERROR (no such file)',
        ]);

        $this->post(route('workers.start', [
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertSessionDoesntHaveErrors();

        $worker->refresh();

        $this->assertSame(WorkerStatus::RUNNING, $worker->status);
        $this->assertNull($worker->error);
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

    public function test_create_worker_with_environment(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('workers.store', [
            'server' => $this->server,
        ]), [
            'name' => 'Test Worker',
            'command' => 'npm run start',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'environment' => [
                ['key' => 'PATH', 'value' => '/home/vito/.local/share/mise/shims:/usr/local/bin:/usr/bin:/bin', 'is_secret' => false],
                ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
            ],
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('workers', [
            'server_id' => $this->server->id,
            'name' => 'Test Worker',
            'command' => 'npm run start',
            'status' => WorkerStatus::RUNNING,
        ]);

        $worker = Worker::where('name', 'Test Worker')->first();
        $this->assertNotNull($worker);
        $this->assertEquals([
            ['key' => 'PATH', 'value' => '/home/vito/.local/share/mise/shims:/usr/local/bin:/usr/bin:/bin', 'is_secret' => false],
            ['key' => 'NODE_ENV', 'value' => 'production', 'is_secret' => false],
        ], $worker->environment);
    }

    public function test_cannot_create_worker_with_legacy_environment_map(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('workers.store', [
            'server' => $this->server,
        ]), [
            'name' => 'Test Worker',
            'command' => 'npm run start',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'environment' => [
                'NODE_ENV' => 'production',
            ],
        ])
            ->assertSessionHasErrors();
    }

    public function test_worker_environment_is_stored_as_array(): void
    {
        $worker = Worker::factory()->withEnvironment([
            'PATH' => '/custom/path',
            'NODE_ENV' => 'production',
        ])->create([
            'server_id' => $this->server->id,
        ]);

        $this->assertIsArray($worker->environment);
        $this->assertEquals('/custom/path', $worker->environmentMap()['PATH']);
        $this->assertEquals('production', $worker->environmentMap()['NODE_ENV']);
    }

    public function test_cannot_edit_site_bootstrap_worker(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

        $this->put(route('workers.update', [
            'server' => $this->server,
            'worker' => $worker,
        ]), [
            'name' => 'renamed',
            'command' => 'renamed command',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
        ])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('workers', [
            'id' => $worker->id,
            'command' => 'renamed command',
        ]);
    }

    public function test_cannot_delete_site_bootstrap_worker(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

        $this->delete(route('workers.destroy', [
            'server' => $this->server,
            'worker' => $worker,
        ]))
            ->assertForbidden();

        $this->assertDatabaseHas('workers', ['id' => $worker->id]);
    }

    public function test_can_still_start_stop_restart_bootstrap_worker(): void
    {
        SSH::fake();
        $this->actingAs($this->user);

        /** @var Worker $worker */
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::STOPPED,
        ]);

        $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $worker->id);

        $this->post(route('workers.start', [
            'server' => $this->server,
            'worker' => $worker,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('workers', [
            'id' => $worker->id,
            'status' => WorkerStatus::RUNNING,
        ]);
    }

    public function test_restart_all_with_site_id_restarts_only_site_workers(): void
    {
        SSH::fake();

        $siteWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);
        $serverWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'status' => WorkerStatus::RUNNING,
        ]);
        $creatingWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::CREATING,
        ]);

        /** @var ProcessManager $handler */
        $handler = $this->server->processManager()->handler();
        $handler->restartAll($this->site->id);

        SSH::assertExecutedContains("supervisorctl update {$siteWorker->id};");
        SSH::assertExecutedContains("restart {$siteWorker->id}:*");
        SSH::assertNotExecutedContains("restart {$serverWorker->id}:*");
        SSH::assertNotExecutedContains("supervisorctl update {$creatingWorker->id};");
        SSH::assertNotExecutedContains('supervisorctl restart all');
    }

    public function test_restart_all_without_site_id_updates_config_and_restarts_all(): void
    {
        SSH::fake();

        /** @var ProcessManager $handler */
        $handler = $this->server->processManager()->handler();
        $handler->restartAll();

        SSH::assertExecutedContains('supervisorctl update');
        SSH::assertExecutedContains('supervisorctl restart all');
    }

    public function test_restart_all_with_site_id_without_workers_executes_nothing(): void
    {
        SSH::fake();

        Worker::factory()->create([
            'server_id' => $this->server->id,
        ]);

        /** @var ProcessManager $handler */
        $handler = $this->server->processManager()->handler();
        $handler->restartAll($this->site->id);

        SSH::assertNotExecutedContains('supervisorctl');
    }

    public function test_resync_updates_worker_statuses(): void
    {
        $this->actingAs($this->user);

        $siteWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::FAILED,
            'error' => 'old error',
        ]);
        $serverWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake(
            "{$siteWorker->id}:{$siteWorker->id}_00   RUNNING   pid 100, uptime 0:00:05\n".
            "{$serverWorker->id}:{$serverWorker->id}_00   STOPPED   Jun 06 10:00 AM"
        );

        $this->post(route('workers.resync', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('success');

        SSH::assertExecutedContains('supervisorctl status');

        $siteWorker->refresh();
        $serverWorker->refresh();
        $this->assertSame(WorkerStatus::RUNNING, $siteWorker->status);
        $this->assertNull($siteWorker->error);
        $this->assertSame(WorkerStatus::STOPPED, $serverWorker->status);
    }

    public function test_resync_marks_fatal_worker_failed_with_error(): void
    {
        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake("{$worker->id}:{$worker->id}_00   FATAL   Exited too quickly (process log may have details)");

        $this->post(route('workers.resync', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors();

        $worker->refresh();
        $this->assertSame(WorkerStatus::FAILED, $worker->status);
        $this->assertStringContainsString('FATAL Exited too quickly', (string) $worker->error);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'sync-worker-statuses-failed',
        ]);
    }

    public function test_resync_does_not_relog_unchanged_failed_worker(): void
    {
        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::FAILED,
        ]);
        $worker->error = "{$worker->id}:{$worker->id}_00: FATAL Exited too quickly";
        $worker->save();

        SSH::fake("{$worker->id}:{$worker->id}_00   FATAL   Exited too quickly");

        $this->post(route('workers.resync', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('server_logs', [
            'type' => 'sync-worker-statuses-failed',
        ]);

        $worker->refresh();
        $this->assertSame(WorkerStatus::FAILED, $worker->status);
    }

    public function test_resync_marks_missing_worker_failed(): void
    {
        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake('unix:///var/run/supervisor.sock no such file');

        $this->post(route('workers.resync', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors();

        $worker->refresh();
        $this->assertSame(WorkerStatus::FAILED, $worker->status);
        $this->assertSame('Process not found in supervisor', $worker->error);
    }

    public function test_resync_site_scope_does_not_touch_server_workers(): void
    {
        $this->actingAs($this->user);

        $siteWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);
        $serverWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake("{$siteWorker->id}:{$siteWorker->id}_00   STOPPED   Jun 06 10:00 AM");

        $this->post(route('workers.resync', ['server' => $this->server, 'site' => $this->site]))
            ->assertSessionDoesntHaveErrors();

        $siteWorker->refresh();
        $serverWorker->refresh();
        $this->assertSame(WorkerStatus::STOPPED, $siteWorker->status);
        $this->assertSame(WorkerStatus::RUNNING, $serverWorker->status);
    }

    public function test_resync_skips_creating_workers(): void
    {
        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::CREATING,
        ]);

        SSH::fake("{$worker->id}:{$worker->id}_00   RUNNING   pid 100, uptime 0:00:05");

        $this->post(route('workers.resync', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors();

        SSH::assertNotExecutedContains('supervisorctl status');

        $worker->refresh();
        $this->assertSame(WorkerStatus::CREATING, $worker->status);
    }

    public function test_restart_all_endpoint_sets_restarting_and_dispatches_job(): void
    {
        Queue::fake();

        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::STOPPED,
            'error' => 'old error',
        ]);
        $creatingWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'status' => WorkerStatus::CREATING,
        ]);

        $this->post(route('workers.restart-all', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors();

        $worker->refresh();
        $creatingWorker->refresh();
        $this->assertSame(WorkerStatus::RESTARTING, $worker->status);
        $this->assertNull($worker->error);
        $this->assertSame(WorkerStatus::CREATING, $creatingWorker->status);

        Queue::assertPushed(RestartAllJob::class);
    }

    public function test_restart_all_endpoint_full_flow_settles_statuses(): void
    {
        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'status' => WorkerStatus::STOPPED,
        ]);

        SSH::fake("{$worker->id}:{$worker->id}_00   RUNNING   pid 100, uptime 0:00:05");

        $this->post(route('workers.restart-all', ['server' => $this->server]))
            ->assertSessionDoesntHaveErrors();

        SSH::assertExecutedContains('supervisorctl restart all');
        SSH::assertExecutedContains('supervisorctl status');

        $worker->refresh();
        $this->assertSame(WorkerStatus::RUNNING, $worker->status);
    }

    public function test_restart_all_endpoint_site_scope_uses_restart_many(): void
    {
        $this->actingAs($this->user);

        $siteWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake("{$siteWorker->id}:{$siteWorker->id}_00   RUNNING   pid 100, uptime 0:00:05");

        $this->post(route('workers.restart-all', ['server' => $this->server, 'site' => $this->site]))
            ->assertSessionDoesntHaveErrors();

        SSH::assertExecutedContains("restart {$siteWorker->id}:*");
        SSH::assertNotExecutedContains('supervisorctl restart all');

        $siteWorker->refresh();
        $this->assertSame(WorkerStatus::RUNNING, $siteWorker->status);
    }

    public function test_restart_all_job_failed_marks_restarting_workers_failed(): void
    {
        $restartingWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RESTARTING,
        ]);
        $stoppedWorker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'status' => WorkerStatus::STOPPED,
        ]);

        (new RestartAllJob($this->server))->failed(new SSHCommandError('boom'));

        $restartingWorker->refresh();
        $stoppedWorker->refresh();
        $this->assertSame(WorkerStatus::FAILED, $restartingWorker->status);
        $this->assertSame('boom', $restartingWorker->error);
        $this->assertSame(WorkerStatus::STOPPED, $stoppedWorker->status);
    }

    public function test_user_role_cannot_resync_or_restart_all(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        $this->actingAs($this->user);

        $this->post(route('workers.resync', ['server' => $this->server]))
            ->assertForbidden();

        $this->post(route('workers.restart-all', ['server' => $this->server]))
            ->assertForbidden();
    }

    public function test_worker_resource_marks_site_bootstrap(): void
    {
        $bootstrap = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);
        $other = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->site->jsonUpdate('type_data', 'bootstrap_worker_id', $bootstrap->id);
        $bootstrap->refresh();
        $other->refresh();

        $this->assertTrue($bootstrap->isSiteBootstrap());
        $this->assertFalse($other->isSiteBootstrap());
    }
}
