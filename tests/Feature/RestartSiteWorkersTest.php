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
        Storage::fake(config('core.logs_disk'));

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake("{$worker->id}:{$worker->id}_00: ERROR (no such file)");

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::FAILED, $worker->status);
        $this->assertSame("{$worker->id}:{$worker->id}_00: ERROR (no such file)", $worker->error);
    }

    public function test_successful_restart_marks_running_and_clears_error(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::FAILED,
            'error' => 'stale error',
        ]);

        SSH::fake(
            "{$worker->id}:{$worker->id}_00: stopped\n".
            "{$worker->id}:{$worker->id}_00: started"
        );

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::RUNNING, $worker->status);
        $this->assertNull($worker->error);
    }

    public function test_worker_left_stopped_is_marked_stopped_with_generic_error(): void
    {
        Storage::fake(config('core.logs_disk'));

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake("{$worker->id}:{$worker->id}_00: stopped");

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::STOPPED, $worker->status);
        $this->assertSame('Unable to restart (stopped)', $worker->error);
    }

    public function test_worker_absent_from_output_is_not_marked_running(): void
    {
        Storage::fake(config('core.logs_disk'));

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake('error: some unexpected supervisor failure');

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::FAILED, $worker->status);
        $this->assertSame('Unable to restart', $worker->error);
    }

    public function test_worker_with_only_benign_statuses_is_not_marked_running(): void
    {
        Storage::fake(config('core.logs_disk'));

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake("{$worker->id}:{$worker->id}_00: ERROR (not running)");

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::STOPPED, $worker->status);
        $this->assertSame('Unable to restart (stopped)', $worker->error);
    }

    public function test_multiprocess_worker_all_started_is_running(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::FAILED,
            'error' => 'stale error',
        ]);

        SSH::fake(
            "{$worker->id}:{$worker->id}_00: stopped\n".
            "{$worker->id}:{$worker->id}_00: started\n".
            "{$worker->id}:{$worker->id}_01: stopped\n".
            "{$worker->id}:{$worker->id}_01: started\n".
            "{$worker->id}:{$worker->id}_02: stopped\n".
            "{$worker->id}:{$worker->id}_02: started"
        );

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::RUNNING, $worker->status);
        $this->assertNull($worker->error);
    }

    public function test_multiprocess_worker_with_one_errored_process_records_only_that_error(): void
    {
        Storage::fake(config('core.logs_disk'));

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake(
            "{$worker->id}:{$worker->id}_00: stopped\n".
            "{$worker->id}:{$worker->id}_00: started\n".
            "{$worker->id}:{$worker->id}_01: stopped\n".
            "{$worker->id}:{$worker->id}_01: ERROR (abnormal termination)"
        );

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::FAILED, $worker->status);
        $this->assertSame("{$worker->id}:{$worker->id}_01: ERROR (abnormal termination)", $worker->error);
    }

    public function test_multiprocess_worker_with_one_started_one_stopped_is_not_running(): void
    {
        Storage::fake(config('core.logs_disk'));

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake(
            "{$worker->id}:{$worker->id}_00: started\n".
            "{$worker->id}:{$worker->id}_01: stopped"
        );

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $worker->refresh();
        $this->assertSame(WorkerStatus::STOPPED, $worker->status);
        $this->assertSame('Unable to restart (stopped)', $worker->error);
    }

    public function test_restart_attributes_errors_to_the_failing_worker_only(): void
    {
        Storage::fake(config('core.logs_disk'));

        $healthy = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);
        $broken = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        SSH::fake(
            "{$healthy->id}:{$healthy->id}_00: stopped\n".
            "{$healthy->id}:{$healthy->id}_00: started\n".
            "{$broken->id}:{$broken->id}_00: ERROR (abnormal termination)"
        );

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        $healthy->refresh();
        $this->assertSame(WorkerStatus::RUNNING, $healthy->status);
        $this->assertNull($healthy->error);

        $broken->refresh();
        $this->assertSame(WorkerStatus::FAILED, $broken->status);
        $this->assertSame("{$broken->id}:{$broken->id}_00: ERROR (abnormal termination)", $broken->error);
    }

    public function test_thrown_restart_error_marks_all_workers_failed(): void
    {
        SSH::fake();
        Storage::fake(config('core.logs_disk'));
        config(['service.services.supervisor.handler' => ThrowingSupervisor::class]);

        $workers = Worker::factory()->count(2)->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        app(RestartSiteWorkers::class)->restart($this->site->fresh());

        foreach ($workers as $worker) {
            $worker->refresh();
            $this->assertSame(WorkerStatus::FAILED, $worker->status);
        }
    }
}

class ThrowingSupervisor extends Supervisor
{
    public function restartMany(array $ids, ?int $siteId = null): string
    {
        $log = ServerLog::log($this->service->server, 'restart-workers', '');

        throw new SSHCommandError(message: 'restart failed', log: $log);
    }
}
