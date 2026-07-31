<?php

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('create job failed keeps worker failed and logs', function () {
    SSH::fake();

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::CREATING,
    ]);

    $job = new CreateJob($worker);
    $job->failed(new Exception('SSH connection failed'));

    $worker->refresh();

    expect($worker->status)->toBe(WorkerStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'create-worker-failed',
    ]);
});

test('manage job failed stores supervisor error from log', function () {
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

    expect($worker->status)->toBe(WorkerStatus::FAILED);
    expect($worker->error)->toBe('10:10_00: ERROR (no such file)');
});

test('manage job failed falls back to message when log has no error lines', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::STARTING,
    ]);

    $job = new ManageJob($worker, 'start', WorkerStatus::RUNNING);
    $job->failed(new SSHConnectionError('Connection failed'));

    $worker->refresh();

    expect($worker->status)->toBe(WorkerStatus::FAILED);
    expect($worker->error)->toBe('Connection failed');
});

test('worker resource exposes error', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'status' => WorkerStatus::FAILED,
        'error' => '10:10_00: ERROR (no such file)',
    ]);

    $resource = (new WorkerResource($worker))->toArray(new Request);

    expect($resource['error'])->toBe('10:10_00: ERROR (no such file)');
});

test('edit job failed sets status to failed and logs', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    $job = new EditJob($worker);
    $job->failed(new Exception('SSH connection failed'));

    $worker->refresh();

    expect($worker->status)->toBe(WorkerStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'edit-worker-failed',
    ]);
});

test('manage job start failed sets status to failed and logs', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::STARTING,
    ]);

    $job = new ManageJob($worker, 'start', WorkerStatus::RUNNING);
    $job->failed(new Exception('Process manager error'));

    $worker->refresh();

    expect($worker->status)->toBe(WorkerStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'start-worker-failed',
    ]);
});

test('manage job stop failed sets status to failed and logs', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::STOPPING,
    ]);

    $job = new ManageJob($worker, 'stop', WorkerStatus::STOPPED);
    $job->failed(new Exception('Process manager error'));

    $worker->refresh();

    expect($worker->status)->toBe(WorkerStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'stop-worker-failed',
    ]);
});

test('manage job restart failed sets status to failed and logs', function () {
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RESTARTING,
    ]);

    $job = new ManageJob($worker, 'restart', WorkerStatus::RUNNING);
    $job->failed(new Exception('Process manager error'));

    $worker->refresh();

    expect($worker->status)->toBe(WorkerStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'restart-worker-failed',
    ]);
});
