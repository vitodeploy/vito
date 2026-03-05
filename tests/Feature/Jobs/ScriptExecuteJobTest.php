<?php

namespace Tests\Feature\Jobs;

use App\Enums\ScriptExecutionStatus;
use App\Jobs\Script\ExecuteJob;
use App\Models\Script;
use App\Models\ScriptExecution;
use App\Models\ServerLog;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScriptExecuteJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_job_failed_sets_status_to_failed_and_logs(): void
    {
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

        $this->assertEquals(ScriptExecutionStatus::FAILED, $execution->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'execute-script-failed',
        ]);
    }
}
