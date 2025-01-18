<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Facades\SSH;
use App\Models\Queue;
use App\Models\Site;
use App\Web\Pages\Servers\Sites\Pages\Queues\Index;
use App\Web\Pages\Servers\Sites\Pages\Queues\Widgets\QueuesList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QueuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_queues()
    {
        $this->actingAs($this->user);

        $queue = Queue::factory()->create([
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
            ->assertSee($queue->command);
    }

    public function test_delete_queue()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $queue = Queue::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        Livewire::test(QueuesList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('delete', $queue->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('queues', [
            'id' => $queue->id,
        ]);
    }

    public function test_create_queue()
    {
        SSH::fake();

        $this->actingAs($this->user);

        Livewire::test(Index::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callAction('create', [
                'command' => 'php artisan queue:work',
                'user' => 'vito',
                'auto_start' => 1,
                'auto_restart' => 1,
                'numprocs' => 1,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('queues', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'command' => 'php artisan queue:work',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'status' => QueueStatus::RUNNING,
        ]);
    }

    public function test_create_queue_as_isolated_user(): void
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

        $this->assertDatabaseHas('queues', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'command' => 'php artisan queue:work',
            'user' => 'example',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'status' => QueueStatus::RUNNING,
        ]);
    }

    public function test_cannot_create_queue_as_invalid_user(): void
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

        $this->assertDatabaseMissing('queues', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'user' => 'example',
        ]);
    }

    public function test_cannot_create_queue_on_another_sites_user(): void
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

        $this->assertDatabaseMissing('queues', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'user' => 'example',
        ]);
    }

    public function test_start_queue(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $queue = Queue::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => QueueStatus::STOPPED,
        ]);

        Livewire::test(QueuesList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('start', $queue->id)
            ->assertSuccessful();

        $this->assertDatabaseHas('queues', [
            'id' => $queue->id,
            'status' => QueueStatus::RUNNING,
        ]);
    }

    public function test_stop_queue(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $queue = Queue::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => QueueStatus::RUNNING,
        ]);

        Livewire::test(QueuesList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('stop', $queue->id)
            ->assertSuccessful();

        $this->assertDatabaseHas('queues', [
            'id' => $queue->id,
            'status' => QueueStatus::STOPPED,
        ]);
    }

    public function test_restart_queue(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $queue = Queue::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => QueueStatus::RUNNING,
        ]);

        Livewire::test(QueuesList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('restart', $queue->id)
            ->assertSuccessful();

        $this->assertDatabaseHas('queues', [
            'id' => $queue->id,
            'status' => QueueStatus::RUNNING,
        ]);
    }

    public function test_show_logs(): void
    {
        SSH::fake('logs');

        $this->actingAs($this->user);

        $queue = Queue::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => QueueStatus::RUNNING,
        ]);

        Livewire::test(QueuesList::class, [
            'server' => $this->server,
            'site' => $this->site,
        ])
            ->callTableAction('logs', $queue->id)
            ->assertSuccessful();
    }
}
