<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Facades\SSH;
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

        $this->delete(
            route('servers.sites.queues.destroy', [
                'server' => $this->server,
                'site' => $this->site,
                'queue' => $queue,
            ])
        )->assertRedirect();

        $this->assertDatabaseMissing('queues', [
            'id' => $queue->id,
        ]);
    }

    public function test_create_queue()
    {
        SSH::fake();

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
            'status' => QueueStatus::RUNNING,
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

        $this->post(
            route('servers.sites.queues.action', [
                'action' => 'start',
                'server' => $this->server,
                'site' => $this->site,
                'queue' => $queue,
            ])
        )->assertSessionDoesntHaveErrors();

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

        $this->post(
            route('servers.sites.queues.action', [
                'action' => 'stop',
                'server' => $this->server,
                'site' => $this->site,
                'queue' => $queue,
            ])
        )->assertSessionDoesntHaveErrors();

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

        $this->post(
            route('servers.sites.queues.action', [
                'action' => 'restart',
                'server' => $this->server,
                'site' => $this->site,
                'queue' => $queue,
            ])
        )->assertSessionDoesntHaveErrors();

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

        $this->get(
            route('servers.sites.queues.logs', [
                'server' => $this->server,
                'site' => $this->site,
                'queue' => $queue,
            ])
        )
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('content', 'logs');
    }
}
