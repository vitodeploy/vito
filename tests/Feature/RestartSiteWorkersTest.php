<?php

namespace Tests\Feature;

use App\Actions\Worker\RestartSiteWorkers;
use App\Enums\WorkerStatus;
use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Models\ServerLog;
use App\Models\Worker;
use App\Services\ProcessManager\Supervisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RestartSiteWorkersTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_worker_restart_is_non_fatal_and_records_error(): void
    {
        SSH::fake();
        Storage::fake(config('core.logs_disk'));
        config(['service.services.supervisor.handler' => ThrowingSupervisor::class]);

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::FAILED, $worker->status);
        $this->assertSame('10:10_00: ERROR (no such file)', $worker->error);
    }

    public function test_successful_restart_marks_running_and_clears_error(): void
    {
        SSH::fake();

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::FAILED,
            'error' => 'stale error',
        ]);

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::RUNNING, $worker->status);
        $this->assertNull($worker->error);
    }
}

class ThrowingSupervisor extends Supervisor
{
    public function restart(int $id, ?int $siteId = null): void
    {
        $log = ServerLog::log($this->service->server, 'restart-worker', "10:10_00: ERROR (no such file)\n");

        throw new SSHCommandError(message: 'restart failed', log: $log);
    }
}
