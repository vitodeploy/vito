<?php

use App\Enums\CommandExecutionStatus;
use App\Facades\SSH;
use App\Jobs\Site\ExecuteCommandJob;
use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\ServerLog;
use App\Models\Site;
use App\SiteTypes\Laravel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('execute command job runs with installed tooling on path', function () {
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
    expect($execution->status)->toEqual(CommandExecutionStatus::COMPLETED);
});

test('execute command job failed sets status to failed and logs', function () {
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

    expect($execution->status)->toEqual(CommandExecutionStatus::FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'execute-command-failed',
    ]);
});
