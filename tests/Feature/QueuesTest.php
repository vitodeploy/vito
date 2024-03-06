<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Models\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            route('servers.sites.queues', [
                'server' => $this->server,
                'site' => $this->site,
            ])
        )
            ->assertOk()
            ->assertSee($queue->command);
    }

    public function test_delete_queue()
    {
        $this->actingAs($this->user);

        $queue = Queue::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
        ]);

        $this->delete(
            route('servers.sites.queues.destroy', [
                'server' => $this->server,
                'site' => $this->site,
                'queue' => $queue,
            ])
        )->assertRedirect();

        $this->assertDatabaseHas('queues', [
            'id' => $queue->id,
            'status' => QueueStatus::DELETING,
        ]);
    }

    public function test_create_queue()
    {
        $this->actingAs($this->user);

        $this->post(
            route('servers.sites.queues.store', [
                'server' => $this->server,
                'site' => $this->site,
            ]),
            [
                'command' => 'php artisan queue:work',
                'user' => 'vito',
                'auto_start' => 1,
                'auto_restart' => 1,
                'numprocs' => 1,
            ]
        )->assertSessionDoesntHaveErrors();

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
