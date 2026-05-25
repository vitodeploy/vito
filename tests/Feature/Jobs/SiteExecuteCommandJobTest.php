<?php

namespace Tests\Feature\Jobs;

use App\Enums\CommandExecutionStatus;
use App\Facades\SSH;
use App\Jobs\Site\ExecuteCommandJob;
use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\ServerLog;
use App\Models\Site;
use App\SiteTypes\Laravel;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteExecuteCommandJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_command_job_runs_with_installed_tooling_on_path(): void
    {
        SSH::fake();

        /** @var Site $site */
        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'isolated-cmd',
            'path' => '/home/isolated-cmd/site.test',
            'type' => Laravel::id(),
            'type_data' => [],
        ]);
        $site->isolatedUser->setToolingVersion('node', '22');
        $site->refresh();

        /** @var Command $command */
        $command = Command::factory()->create([
            'site_id' => $site->id,
            'command' => 'npm -v',
        ]);

        /** @var CommandExecution $execution */
        $execution = CommandExecution::factory()->create([
            'command_id' => $command->id,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'status' => CommandExecutionStatus::EXECUTING,
            'variables' => [],
        ]);

        $log = ServerLog::newLog($this->server, 'execute-command');
        $log->save();

        (new ExecuteCommandJob($execution, $command, $log))->handle();

        SSH::assertExecutedContains('/home/isolated-cmd/.local/share/mise/shims');

        $execution->refresh();
        $this->assertEquals(CommandExecutionStatus::COMPLETED, $execution->status);
    }

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
