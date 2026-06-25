<?php

namespace App\Jobs\Workflow;

use App\Actions\Workflow\RunWorkflow;
use App\DTOs\SocketEventDTO;
use App\DTOs\WorkflowActionDTO;
use App\Enums\WorkflowRunStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Http\Resources\WorkflowRunResource;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected WorkflowRun $run,
        protected User $user,
        protected Workflow $workflow,
        protected WorkflowActionDTO $executionTree,
        protected array $input,
    ) {}

    public function handle(): void
    {
        $this->run("workflow-{$this->workflow->id}", function () {
            // set all queue drivers to sync for underlying actions
            config()->set('queue.connections.ssh.driver', 'sync');
            config()->set('queue.connections.default.driver', 'sync');

            if ($this->run->verbose && $this->run->log_disk && $this->run->log_path) {
                SSH::useLog($this->run->log_disk, $this->run->log_path, function (string $chunk): void {
                    $this->broadcastLogContent($chunk);
                });
            }
            if ($this->input['inputs']) {
                $this->executionTree->inputs = $this->input['inputs'];
            }
            try {
                app(RunWorkflow::class)->executeAction(
                    $this->run,
                    $this->user,
                    $this->workflow,
                    $this->executionTree,
                    $this->input['inputs'] ?? [],
                );
            } finally {
                SSH::clearLog();
            }
            $this->run->status = WorkflowRunStatus::COMPLETED;
            $this->run->save();
            $this->broadcastRunUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->run->status = WorkflowRunStatus::FAILED;
        $this->run->save();
        $this->broadcastRunUpdate();
        Log::error('run-workflow-failed', ['error' => $e->getMessage()]);
    }

    private function broadcastRunUpdate(): void
    {
        $this->run->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->workflow->project_id,
            type: 'workflow-run.updated',
            data: new WorkflowRunResource($this->run),
        ));
    }

    private function broadcastLogContent(string $content): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->workflow->project_id,
            type: 'workflow-run.log-content',
            data: [
                'id' => $this->run->id,
                'content' => $content,
            ],
        ));
    }
}
