<?php

namespace App\Jobs\Script;

use App\DTOs\SocketEventDTO;
use App\Enums\ScriptExecutionStatus;
use App\Events\SocketEvent;
use App\Http\Resources\ScriptExecutionResource;
use App\Models\ScriptExecution;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class ExecuteJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected ScriptExecution $execution,
        protected ServerLog $log,
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->execution->server_id}", function () {
            $server = $this->execution->server;
            $script = $this->execution->script;
            if (! $server || ! $script->user?->can('execute', [$script, $server])) {
                throw new RuntimeException('The script owner no longer has access to this server.');
            }
            $content = $this->execution->getContent();
            $this->execution->server_log_id = $this->log->id;
            $this->execution->save();
            $server->os()->runScript('~/', $content, $this->log, $this->execution->user);
            $this->execution->status = ScriptExecutionStatus::COMPLETED;
            $this->execution->save();
            $this->broadcastExecutionUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->execution->status = ScriptExecutionStatus::FAILED;
        $this->execution->save();
        $this->broadcastExecutionUpdate();
        if ($this->execution->server) {
            ServerLog::log($this->execution->server, 'execute-script-failed', $e->getMessage());
        }
    }

    private function broadcastExecutionUpdate(): void
    {
        $this->execution->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->execution->server->project_id ?? 0,
            type: 'script-execution.updated',
            data: new ScriptExecutionResource($this->execution),
            userId: $this->execution->script->user_id,
        ));
    }
}
