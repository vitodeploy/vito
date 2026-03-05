<?php

namespace App\Jobs\Site;

use App\Enums\CommandExecutionStatus;
use App\Models\Command;
use App\Models\CommandExecution;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExecuteCommandJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected CommandExecution $execution,
        protected Command $command,
        protected ServerLog $log,
    ) {}

    public function handle(): void
    {
        $this->run("site-{$this->command->site_id}", function () {
            $content = $this->execution->getContent();
            $this->execution->server_log_id = $this->log->id;
            $this->execution->save();
            $this->execution->server->os()->runScript(
                path: $this->command->site->path,
                script: $content,
                serverLog: $this->log,
                user: $this->command->site->user,
                variables: $this->execution->variables,
                aliases: $this->command->site->environmentAliases(),
            );
            $this->execution->status = CommandExecutionStatus::COMPLETED;
            $this->execution->save();
        });
    }

    public function failed(Exception $e): void
    {
        $this->execution->status = CommandExecutionStatus::FAILED;
        $this->execution->save();
        ServerLog::log($this->execution->server, 'execute-command-failed', $e->getMessage());
    }
}
