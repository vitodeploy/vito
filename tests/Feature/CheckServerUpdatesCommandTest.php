<?php

namespace Tests\Feature;

use App\Enums\ServerStatus;
use App\Jobs\Server\CheckForUpdatesJob;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CheckServerUpdatesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_job_for_ready_servers(): void
    {
        Bus::fake();

        $this->artisan('servers:check-updates')->assertSuccessful();

        Bus::assertDispatched(CheckForUpdatesJob::class, function (CheckForUpdatesJob $job) {
            return $job->queue === 'ssh';
        });
    }

    public function test_does_not_dispatch_for_disconnected_servers(): void
    {
        Bus::fake();

        $this->server->update(['status' => ServerStatus::DISCONNECTED]);

        $this->artisan('servers:check-updates')->assertSuccessful();

        Bus::assertNotDispatched(CheckForUpdatesJob::class);
    }

    public function test_dispatches_for_each_ready_server(): void
    {
        Bus::fake();

        Server::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'status' => ServerStatus::READY,
        ]);

        Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'status' => ServerStatus::DISCONNECTED,
        ]);

        $this->artisan('servers:check-updates')->assertSuccessful();

        Bus::assertDispatchedTimes(CheckForUpdatesJob::class, 3);
    }
}
