<?php

namespace App\Actions\Workflow;

use App\DTOs\WorkflowActionDTO;
use App\Enums\WorkflowRunStatus;
use App\Exceptions\AppError;
use App\Jobs\Workflow\RunJob;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class RunWorkflow
{
    public function run(User $user, Workflow $workflow, array $input): WorkflowRun
    {
        $executionTree = $workflow->getExecutionTree();

        if (! $executionTree) {
            throw new AppError('Workflow has no starting action');
        }

        Validator::make($input, [
            'inputs' => 'array',
            'verbose' => 'boolean',
        ])->validate();

        $run = new WorkflowRun([
            'workflow_id' => $workflow->id,
            'user_id' => $user->id,
            'status' => WorkflowRunStatus::RUNNING,
            'current_node_id' => $executionTree->id,
            'current_node_label' => $executionTree->label,
            'verbose' => $input['verbose'] ?? false,
        ]);
        $run->save();

        $run->log('Starting workflow ['.$workflow->name.']');
        $run->refresh();

        dispatch(new RunJob($run, $user, $workflow, $executionTree, $input))->onQueue('ssh');

        return $run;
    }

    public function executeAction(WorkflowRun $run, User $user, Workflow $workflow, ?WorkflowActionDTO $workflowActionDto, ?array $input, ?int $accessTokenId = null): void
    {
        if (! $workflowActionDto) {
            return;
        }

        $this->authorizeExecution($user, $workflow, $accessTokenId);

        $resolvedInput = $this->resolveInputs($input ?? [], $workflowActionDto->inputs ?? []);

        $run->current_node_id = $workflowActionDto->id;
        $run->current_node_label = $workflowActionDto->label;
        $run->save();

        $run->log('Running action: '.$workflowActionDto->label);

        try {
            $output = $workflowActionDto->handler($user, $workflow)->run($resolvedInput);
        } catch (\Throwable $e) {
            $run->log('Workflow action failed: '.$e->getMessage());
            $this->executeAction($run, $user, $workflow, $workflowActionDto->failure, $input, $accessTokenId);

            return;
        }

        $this->executeAction($run, $user, $workflow, $workflowActionDto->success, $output, $accessTokenId);
    }

    private function authorizeExecution(User $user, Workflow $workflow, ?int $accessTokenId): void
    {
        if ($accessTokenId !== null) {
            $token = $user->tokens()->find($accessTokenId);
            $expiration = config('sanctum.expiration');

            if (! $token ||
                ($token->expires_at && $token->expires_at->isPast()) ||
                ($expiration && $token->created_at->lte(now()->subMinutes($expiration))) ||
                ! $token->can('write')) {
                throw new AuthorizationException('The workflow API token is no longer valid.');
            }

            $user->withAccessToken($token);
        }

        Gate::forUser($user)->authorize('update', $workflow);
    }

    /**
     * @param  array<string, mixed>  $previousOutputs
     * @param  array<string, mixed>  $actionInputs
     * @return array<string, mixed>
     */
    private function resolveInputs(array $previousOutputs, array $actionInputs): array
    {
        $resolvedInputs = [];

        foreach ($actionInputs as $key => $value) {
            if (is_string($value)) {
                if (preg_match('/^\{\{?(\w+)\}?\}$/', $value, $matches)) {
                    $placeholderKey = $matches[1];

                    if (array_key_exists($placeholderKey, $previousOutputs)) {
                        $resolvedInputs[$key] = $previousOutputs[$placeholderKey];
                    } else {
                        $resolvedInputs[$key] = $value;
                    }
                } else {
                    $resolvedInputs[$key] = $value;
                }
            } else {
                $resolvedInputs[$key] = $value;
            }
        }

        foreach ($resolvedInputs as $key => $value) {
            if (is_string($value) && ! preg_match('/^\{\{?(\w+)\}?\}$/', $value)) {
                $resolvedInputs[$key] = $this->interpolateString($value, $previousOutputs);
            }
        }

        return array_merge($previousOutputs, $resolvedInputs);
    }

    /**
     * @param  array<string, mixed>  $previousOutputs
     */
    private function interpolateString(string $string, array $previousOutputs): string
    {
        return preg_replace_callback('/\{\{?(\w+)\}?\}/', function ($matches) use ($previousOutputs) {
            $placeholderKey = $matches[1];

            if (array_key_exists($placeholderKey, $previousOutputs)) {
                return (string) $previousOutputs[$placeholderKey];
            }

            return $matches[0];
        }, $string);
    }
}
