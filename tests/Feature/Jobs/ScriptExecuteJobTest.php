<?php

use App\Enums\ScriptExecutionStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Jobs\Script\ExecuteJob;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\Server;
use App\Models\ServerLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('execute job failed sets status to failed and logs', function () {
    /** @var Script $script */
    $script = Script::factory()->create([
        'user_id' => $this->user->id,
    ]);

    /** @var ScriptExecution $execution */
    $execution = ScriptExecution::factory()->create([
        'script_id' => $script->id,
        'server_id' => $this->server->id,
        'status' => ScriptExecutionStatus::EXECUTING,
    ]);

    $log = ServerLog::newLog($this->server, 'execute-script');
    $log->save();

    $job = new ExecuteJob($execution, $log);
    $job->failed(new Exception('Script execution timed out'));

    $execution->refresh();

    expect($execution->status)->toEqual(ScriptExecutionStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'execute-script-failed',
    ]);
});

function vitoPestFeatureJobsScriptExecuteJobTestJob(Script $script, Server $server): ExecuteJob
{
    /** @var ScriptExecution $execution */
    $execution = ScriptExecution::factory()->create([
        'script_id' => $script->id,
        'server_id' => $server->id,
        'status' => ScriptExecutionStatus::EXECUTING,
    ]);

    $log = ServerLog::newLog($server, 'execute-script');
    $log->save();

    return new ExecuteJob($execution, $log);
}

test('execute job runs the script and sends the update only to the script owner', function () {
    SSH::fake('output');
    Event::fake([SocketEvent::class]);
    $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'echo ${NAME}']);

    dispatch(vitoPestFeatureJobsScriptExecuteJobTestJob($script, $this->server));

    SSH::assertExecutedContains('echo ${NAME}');
    $this->assertDatabaseHas('script_executions', ['script_id' => $script->id, 'status' => ScriptExecutionStatus::COMPLETED]);
    Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event): bool => $event->data->type === 'script-execution.updated'
        && $event->data->userId === $this->user->id);
});

test('execute job refuses to run once the script owner lost write access to the server', function (?UserRole $role) {
    SSH::fake('output');
    $script = Script::factory()->create(['user_id' => $this->user->id, 'content' => 'touch /tmp/pwned']);
    $job = vitoPestFeatureJobsScriptExecuteJobTestJob($script, $this->server);

    $membership = $this->server->project->users()->where('user_id', $this->user->id);
    $role === null ? $membership->delete() : $membership->update(['role' => $role]);

    dispatch($job);

    SSH::assertNotExecutedContains('touch /tmp/pwned');
    $this->assertDatabaseHas('script_executions', ['script_id' => $script->id, 'status' => ScriptExecutionStatus::FAILED]);
    $this->assertDatabaseHas('server_logs', ['server_id' => $this->server->id, 'type' => 'execute-script-failed']);
})->with([
    'removed from the project' => [null],
    'downgraded to user' => [UserRole::USER],
]);

test('execute job marks the execution failed when its server is gone', function () {
    Event::fake([SocketEvent::class]);
    $script = Script::factory()->create(['user_id' => $this->user->id]);
    $job = vitoPestFeatureJobsScriptExecuteJobTestJob($script, $this->server);
    ScriptExecution::query()->where('script_id', $script->id)->update(['server_id' => null]);

    dispatch($job);

    $this->assertDatabaseHas('script_executions', ['script_id' => $script->id, 'status' => ScriptExecutionStatus::FAILED]);
    Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event): bool => $event->data->projectId === 0
        && $event->data->userId === $this->user->id);
});
