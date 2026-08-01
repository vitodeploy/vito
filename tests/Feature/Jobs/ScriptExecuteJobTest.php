<?php

use App\Enums\ScriptExecutionStatus;
use App\Jobs\Script\ExecuteJob;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\ServerLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
