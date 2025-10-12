<?php

namespace App\Actions\Workflow;

use App\DTOs\WorkflowActionDTO;
use App\Enums\WorkflowRunStatus;
use App\Exceptions\AppError;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\Log;

class RunWorkflow
{
    public function run(User $user, Workflow $workflow, array $input): void
    {
        $executionTree = $workflow->getExecutionTree();

        if (! $executionTree) {
            throw new AppError('Workflow has no starting action');
        }

        dispatch(function () use ($user, $workflow, $executionTree, $input) {
            $run = new WorkflowRun([
                'workflow_id' => $workflow->id,
                'user_id' => $user->id,
                'status' => WorkflowRunStatus::RUNNING,
            ]);
            $run->save();
            $this->executeAction($run, $user, $workflow, $executionTree, $input);
        })->onQueue('ssh');
    }

    private function executeAction(WorkflowRun $run, User $user, Workflow $workflow, ?WorkflowActionDTO $workflowActionDto, ?array $input): void
    {
        if (! $workflowActionDto) {
            return;
        }

        $outout = [];
        $run->current_node_id = $workflowActionDto->id;
        $run->current_node_label = $workflowActionDto->label;
        $run->save();

        try {
            $output = $workflowActionDto->handler($user, $workflow)->run($input ?? []);
            $this->executeAction($run, $user, $workflow, $workflowActionDto->success, $output);
        } catch (\Throwable $e) {
            Log::error('Workflow action failed: '.$e->getMessage());
            $this->executeAction($run, $user, $workflow, $workflowActionDto->failure, $outout);
        }
    }
}
