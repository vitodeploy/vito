<?php

use App\Actions\Worker\RestartSiteWorkers;
use App\Enums\WorkerStatus;
use App\Facades\SSH;
use App\Models\ServerLog;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\ThrowingSupervisor;

uses(RefreshDatabase::class);

test('failed worker restart is non fatal and records error', function () {
    Storage::fake(config('core.logs_disk'));

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake("{$worker->id}:{$worker->id}_00: ERROR (no such file)");

    app(RestartSiteWorkers::class)->restart($this->site->fresh());

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::FAILED);
    expect($worker->error)->toBe("{$worker->id}:{$worker->id}_00: ERROR (no such file)");
});

test('a worker that has not been installed yet does not fail the restart of its siblings', function () {
    $installed = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
        'stdout_logfile' => '/home/vito/.logs/workers/legacy.log',
    ]);
    $pending = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::CREATING,
    ]);
    SSH::fake("{$installed->id}:{$installed->id}_00: started\n{$pending->id}:{$pending->id}_00: started");

    app(RestartSiteWorkers::class)->restart($this->site->fresh());

    $this->assertDatabaseHas('workers', ['id' => $installed->id, 'status' => WorkerStatus::RUNNING, 'error' => null]);
    SSH::assertExecutedContains('supervisorctl');
    SSH::assertExecutedContains("install -d -o root -g 'vito' -m 750 '/var/log/vito/workers/vito'");
});

test('successful restart marks running and clears error', function () {
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
    expect($worker->status)->toBe(WorkerStatus::RUNNING);
    expect($worker->error)->toBeNull();
});

test('supervisor group updates do not replace process restart results', function (?string $processStatus, WorkerStatus $expected, ?string $error) {
    Storage::fake(config('core.logs_disk'));
    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
        'error' => 'stale error',
    ]);
    SSH::fake("{$worker->id}: changed\n{$worker->id}: stopped\n{$worker->id}: updated process group\n".
        ($processStatus === null ? '' : "{$worker->id}:{$worker->id}_00: {$processStatus}"));

    app(RestartSiteWorkers::class)->restart($this->site->fresh());

    $this->assertDatabaseHas('workers', [
        'id' => $worker->id,
        'status' => $expected,
        'error' => $error === 'ERROR (spawn error)' ? "{$worker->id}:{$worker->id}_00: {$error}" : $error,
    ]);
})->with([
    ['started', WorkerStatus::RUNNING, null],
    ['stopped', WorkerStatus::STOPPED, 'Unable to restart (stopped)'],
    ['ERROR (spawn error)', WorkerStatus::FAILED, 'ERROR (spawn error)'],
    [null, WorkerStatus::FAILED, 'Unable to restart'],
]);

test('worker left stopped is marked stopped with generic error', function () {
    Storage::fake(config('core.logs_disk'));

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake("{$worker->id}:{$worker->id}_00: stopped");

    app(RestartSiteWorkers::class)->restart($this->site->fresh());

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::STOPPED);
    expect($worker->error)->toBe('Unable to restart (stopped)');
});

test('worker absent from output is not marked running', function () {
    Storage::fake(config('core.logs_disk'));

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake('error: some unexpected supervisor failure');

    app(RestartSiteWorkers::class)->restart($this->site->fresh());

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::FAILED);
    expect($worker->error)->toBe('Unable to restart');
});

test('worker with only benign statuses is not marked running', function () {
    Storage::fake(config('core.logs_disk'));

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake("{$worker->id}:{$worker->id}_00: ERROR (not running)");

    app(RestartSiteWorkers::class)->restart($this->site->fresh());

    $worker->refresh();
    expect($worker->status)->toBe(WorkerStatus::STOPPED);
    expect($worker->error)->toBe('Unable to restart (stopped)');
});

test('multiprocess worker all started is running', function () {
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
    expect($worker->status)->toBe(WorkerStatus::RUNNING);
    expect($worker->error)->toBeNull();
});

test('multiprocess worker with one errored process records only that error', function () {
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
    expect($worker->status)->toBe(WorkerStatus::FAILED);
    expect($worker->error)->toBe("{$worker->id}:{$worker->id}_01: ERROR (abnormal termination)");
});

test('multiprocess worker with one started one stopped is not running', function () {
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
    expect($worker->status)->toBe(WorkerStatus::STOPPED);
    expect($worker->error)->toBe('Unable to restart (stopped)');
});

test('restart attributes errors to the failing worker only', function () {
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
    expect($healthy->status)->toBe(WorkerStatus::RUNNING);
    expect($healthy->error)->toBeNull();

    $broken->refresh();
    expect($broken->status)->toBe(WorkerStatus::FAILED);
    expect($broken->error)->toBe("{$broken->id}:{$broken->id}_00: ERROR (abnormal termination)");
});

test('thrown restart error marks all workers failed', function () {
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
        expect($worker->status)->toBe(WorkerStatus::FAILED);
    }
});

test('restart rewrites worker config before restarting', function () {
    Storage::fake(config('core.logs_disk'));

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    $ssh = SSH::fake(
        "{$worker->id}:{$worker->id}_00: stopped\n".
        "{$worker->id}:{$worker->id}_00: started"
    );

    app(RestartSiteWorkers::class)->restart($this->site->fresh());

    $ssh->assertExecutedContains("/etc/supervisor/conf.d/{$worker->id}.conf");
});

test('restart failure is written to the deploy log', function () {
    Storage::fake(config('core.logs_disk'));

    $worker = Worker::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => WorkerStatus::RUNNING,
    ]);

    SSH::fake("{$worker->id}:{$worker->id}_00: ERROR (abnormal termination)");

    $log = ServerLog::newLog($this->server, 'deploy-test');
    $log->save();

    app(RestartSiteWorkers::class)->restart($this->site->fresh(), $log);

    $this->assertStringContainsString('Failed to restart worker(s): '.$worker->id, (string) $log->getContent());
});
