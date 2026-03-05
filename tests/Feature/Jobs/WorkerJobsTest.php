<?php

namespace Tests\Feature\Jobs;

use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Jobs\Worker\CreateJob;
use App\Jobs\Worker\EditJob;
use App\Jobs\Worker\ManageJob;
use App\Models\Worker;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_job_failed_deletes_worker_and_logs(): void
    {
        SSH::fake();

        $worker = Worker::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => WorkerStatus::CREATING,
        ]);

        $job = new CreateJob($worker);
        $job->failed(new Exception('SSH connection failed'));

        $this->assertDatabaseMissing('workers', [
            'id' => $worker->id,
        ]);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'create-worker-failed',
        ]);
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

        $this->assertEquals(WorkerStatus::FAILED, $worker->status);

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

        $this->assertEquals(WorkerStatus::FAILED, $worker->status);

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

        $this->assertEquals(WorkerStatus::FAILED, $worker->status);

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

        $this->assertEquals(WorkerStatus::FAILED, $worker->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'restart-worker-failed',
        ]);
    }
}
