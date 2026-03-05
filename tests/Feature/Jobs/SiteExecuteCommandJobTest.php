<?php

namespace Tests\Feature\Jobs;

use App\Enums\CommandExecutionStatus;
use App\Jobs\Site\ExecuteCommandJob;
use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\ServerLog;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteExecuteCommandJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_command_job_failed_sets_status_to_failed_and_logs(): void
    {
        /** @var Command $command */
        $command = Command::factory()->create([
            'site_id' => $this->site->id,
        ]);

        /** @var CommandExecution $execution */
        $execution = CommandExecution::factory()->create([
            'command_id' => $command->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => CommandExecutionStatus::EXECUTING,
        ]);

        $log = ServerLog::newLog($this->server, 'execute-command');
        $log->save();

        $job = new ExecuteCommandJob($execution, $command, $log);
        $job->failed(new Exception('Command execution timed out'));

        $execution->refresh();

        $this->assertEquals(CommandExecutionStatus::FAILED, $execution->status);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'type' => 'execute-command-failed',
        ]);
    }
}
