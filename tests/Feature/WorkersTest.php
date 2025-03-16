<?php

namespace Tests\Feature;

use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\Worker;
use App\Models\Site;
use App\Web\Pages\Servers\Sites\Pages\Workers\Index;
use App\Web\Pages\Servers\Sites\Pages\Workers\Widgets\WorkersList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkersTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_workers(): void
    {
        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->get(
            Index::getUrl([
                'server' => $this->server,
                'site' => $this->site,
            ])
        )
            ->assertSuccessful()
            ->assertSee($worker->command);
    }

    public function test_delete_worker(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        Livewire::test(WorkersList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('delete', $worker->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('workers', [
            'id' => $worker->id,
        ]);
    }

    public function test_create_worker(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(Index::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('create', [
                'command' => 'php artisan worker:work',
                'user' => 'vito',
                'auto_start' => 1,
                'auto_restart' => 1,
                'numprocs' => 1,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('workers', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
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

        Livewire::test(Index::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('create', [
                'command' => 'php artisan queue:work',
                'user' => 'example',
                'auto_start' => 1,
                'auto_restart' => 1,
                'numprocs' => 1,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('workers', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'command' => 'php artisan queue:work',
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

        Livewire::test(Index::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('create', [
                'command' => 'php artisan queue:work',
                'user' => 'example',
                'auto_start' => 1,
                'auto_restart' => 1,
                'numprocs' => 1,
            ])
            ->assertHasActionErrors();

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

        Livewire::test(Index::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('create', [
                'command' => 'php artisan queue:work',
                'user' => 'example',
                'auto_start' => 1,
                'auto_restart' => 1,
                'numprocs' => 1,
            ])
            ->assertHasActionErrors();

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

        Livewire::test(WorkersList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('start', $worker->id)
            ->assertSuccessful();

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

        Livewire::test(WorkersList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('stop', $worker->id)
            ->assertSuccessful();

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

        Livewire::test(WorkersList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('restart', $worker->id)
            ->assertSuccessful();

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

        Livewire::test(WorkersList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('logs', $worker->id)
            ->assertSuccessful();
    }
}
