<?php

namespace Tests\Feature\Http;

use App\Enums\QueueStatus;
use App\Http\Livewire\Queues\CreateQueue;
use App\Http\Livewire\Queues\QueuesList;
use App\Models\Queue;
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

        Livewire::test(QueuesList::class, ['site' => $this->site])
            ->assertSeeText($queue->command);
    }

    public function test_delete_queue()
    {
        $this->actingAs($this->user);

        $queue = Queue::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        Livewire::test(QueuesList::class, ['site' => $this->site])
            ->set('deleteId', $queue->id)
            ->call('delete')
            ->assertDispatchedBrowserEvent('confirmed', true);
    }

    public function test_create_queue()
    {
        $this->actingAs($this->user);

        Livewire::test(CreateQueue::class, ['site' => $this->site])
            ->set('command', 'php artisan queue:work')
            ->set('user', 'vito')
            ->set('auto_start', 1)
            ->set('auto_restart', 1)
            ->set('numprocs', 1)
            ->call('create')
            ->assertDispatchedBrowserEvent('created', true);

        $this->assertDatabaseHas('queues', [
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'command' => 'php artisan queue:work',
            'user' => 'vito',
            'auto_start' => 1,
            'auto_restart' => 1,
            'numprocs' => 1,
            'status' => QueueStatus::CREATING,
        ]);
    }
}
