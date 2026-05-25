<?php

namespace Tests\Feature\Jobs;

use App\Enums\WorkerStatus;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Facades\SSH;
use App\Http\Resources\WorkerResource;
use App\Jobs\Worker\CreateJob;
use App\Jobs\Worker\EditJob;
use App\Jobs\Worker\ManageJob;
use App\Models\ServerLog;
use App\Models\Worker;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkerJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_job_failed_keeps_worker_failed_and_logs(): void
    {
        SSH::fake();

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::CREATING,
        ]);

        $job = new CreateJob($worker);
        $job->failed(new Exception('SSH connection failed'));

        $worker->refresh();

        $this->assertSame(WorkerStatus::FAILED, $worker->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'create-worker-failed',
        ]);
    }

    public function test_manage_job_failed_stores_supervisor_error_from_log(): void
    {
        Storage::fake(config('core.logs_disk'));

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::STARTING,
        ]);

        $log = ServerLog::log($this->server, 'start-worker', "+ supervisorctl start 10:*\n10:10_00: ERROR (no such file)\n");

        $job = new ManageJob($worker, 'start', WorkerStatus::RUNNING);
        $job->failed(new SSHCommandError(message: 'SSH command failed with an error', log: $log));

        $worker->refresh();

        $this->assertSame(WorkerStatus::FAILED, $worker->status);
        $this->assertSame('10:10_00: ERROR (no such file)', $worker->error);
    }

    public function test_manage_job_failed_falls_back_to_message_when_log_has_no_error_lines(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::STARTING,
        ]);

        $job = new ManageJob($worker, 'start', WorkerStatus::RUNNING);
        $job->failed(new SSHConnectionError('Connection failed'));

        $worker->refresh();

        $this->assertSame(WorkerStatus::FAILED, $worker->status);
        $this->assertSame('Connection failed', $worker->error);
    }

    public function test_worker_resource_exposes_error(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'status' => WorkerStatus::FAILED,
            'error' => '10:10_00: ERROR (no such file)',
        ]);

        $resource = (new WorkerResource($worker))->toArray(new Request);

        $this->assertSame('10:10_00: ERROR (no such file)', $resource['error']);
    }

    public function test_edit_job_failed_sets_status_to_failed_and_logs(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RUNNING,
        ]);

        $job = new EditJob($worker);
        $job->failed(new Exception('SSH connection failed'));

        $worker->refresh();

        $this->assertSame(WorkerStatus::FAILED, $worker->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'edit-worker-failed',
        ]);
    }

    public function test_manage_job_start_failed_sets_status_to_failed_and_logs(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::STARTING,
        ]);

        $job = new ManageJob($worker, 'start', WorkerStatus::RUNNING);
        $job->failed(new Exception('Process manager error'));

        $worker->refresh();

        $this->assertSame(WorkerStatus::FAILED, $worker->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'start-worker-failed',
        ]);
    }

    public function test_manage_job_stop_failed_sets_status_to_failed_and_logs(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::STOPPING,
        ]);

        $job = new ManageJob($worker, 'stop', WorkerStatus::STOPPED);
        $job->failed(new Exception('Process manager error'));

        $worker->refresh();

        $this->assertSame(WorkerStatus::FAILED, $worker->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'stop-worker-failed',
        ]);
    }

    public function test_manage_job_restart_failed_sets_status_to_failed_and_logs(): void
    {
        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::RESTARTING,
        ]);

        $job = new ManageJob($worker, 'restart', WorkerStatus::RUNNING);
        $job->failed(new Exception('Process manager error'));

        $worker->refresh();

        $this->assertSame(WorkerStatus::FAILED, $worker->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'restart-worker-failed',
        ]);
    }
}
