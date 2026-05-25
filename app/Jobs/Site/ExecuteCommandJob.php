<?php

namespace App\Jobs\Site;

use App\DTOs\SocketEventDTO;
use App\Enums\CommandExecutionStatus;
use App\Events\SocketEvent;
use App\Http\Resources\CommandExecutionResource;
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
    ) {
        $this->onQueue('ssh');
    }

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
                variables: array_merge(
                    $this->command->site->environmentVariables(),
                    $this->command->site->type()->deploymentEnvironment(),
                    $this->execution->variables,
                ),
                aliases: $this->command->site->environmentAliases(),
            );
            $this->execution->status = CommandExecutionStatus::COMPLETED;
            $this->execution->save();
            $this->broadcastExecutionUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->execution->status = CommandExecutionStatus::FAILED;
        $this->execution->save();
        $this->broadcastExecutionUpdate();
        ServerLog::log($this->execution->server, 'execute-command-failed', $e->getMessage());
    }

    private function broadcastExecutionUpdate(): void
    {
        $this->execution->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->execution->server->project_id,
            type: 'command-execution.updated',
            data: new CommandExecutionResource($this->execution),
        ));
    }
}
