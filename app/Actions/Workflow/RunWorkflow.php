<?php

namespace App\Actions\Workflow;

use App\DTOs\WorkflowActionDTO;
use App\Enums\WorkflowRunStatus;
use App\Exceptions\AppError;
use App\Facades\SSH;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
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

        dispatch(function () use ($run, $user, $workflow, $executionTree, $input) {
            // set all queue drivers to sync for underlying actions
            config()->set('queue.connections.ssh.driver', 'sync');
            config()->set('queue.connections.default.driver', 'sync');

            if ($run->verbose && $run->log_disk && $run->log_path) {
                SSH::useLog($run->log_disk, $run->log_path);
            }
            if ($input['inputs']) {
                $executionTree->inputs = $input['inputs'];
            }
            $this->executeAction($run, $user, $workflow, $executionTree, $input['inputs'] ?? []);
            $run->status = WorkflowRunStatus::COMPLETED;
            $run->save();
        })->catch(function () use ($run) {
            $run->status = WorkflowRunStatus::FAILED;
            $run->save();
        })->onQueue('ssh');

        return $run;
    }

    private function executeAction(WorkflowRun $run, User $user, Workflow $workflow, ?WorkflowActionDTO $workflowActionDto, ?array $input): void
    {
        if (! $workflowActionDto) {
            return;
        }

        // Merge input with $workflowActionDto->inputs and resolve placeholders
        $resolvedInput = $this->resolveInputs($input ?? [], $workflowActionDto->inputs ?? []);

        $run->current_node_id = $workflowActionDto->id;
        $run->current_node_label = $workflowActionDto->label;
        $run->save();

        $run->log('Running action: '.$workflowActionDto->label);

        try {
            $output = $workflowActionDto->handler($user, $workflow)->run($resolvedInput);
            $this->executeAction($run, $user, $workflow, $workflowActionDto->success, $output);
        } catch (\Throwable $e) {
            $run->log('Workflow action failed: '.$e->getMessage());
            $this->executeAction($run, $user, $workflow, $workflowActionDto->failure, $input);
        }
    }

    /**
     * Resolve inputs by merging previous outputs with current action inputs and replacing placeholders
     *
     * @param  array<string, mixed>  $previousOutputs
     * @param  array<string, mixed>  $actionInputs
     * @return array<string, mixed>
     */
    private function resolveInputs(array $previousOutputs, array $actionInputs): array
    {
        $resolvedInputs = [];

        // First pass: resolve exact placeholders and regular values
        foreach ($actionInputs as $key => $value) {
            if (is_string($value)) {
                // Handle exact placeholder matches
                if (preg_match('/^\{\{?(\w+)\}?\}$/', $value, $matches)) {
                    // This is an exact placeholder like {server_id} or {{server_id}}
                    $placeholderKey = $matches[1];

                    if (array_key_exists($placeholderKey, $previousOutputs)) {
                        // Replace placeholder with actual value from previous output
                        $resolvedInputs[$key] = $previousOutputs[$placeholderKey];
                    } else {
                        // Placeholder not found in previous outputs, keep the placeholder as is
                        $resolvedInputs[$key] = $value;
                    }
                } else {
                    // This might contain interpolated placeholders, resolve later
                    $resolvedInputs[$key] = $value;
                }
            } else {
                // Regular input value, use as is
                $resolvedInputs[$key] = $value;
            }
        }

        // Second pass: resolve string interpolation using original previous outputs
        // This ensures string interpolation uses the original values, not the overridden ones
        foreach ($resolvedInputs as $key => $value) {
            if (is_string($value) && ! preg_match('/^\{\{?(\w+)\}?\}$/', $value)) {
                $resolvedInputs[$key] = $this->interpolateString($value, $previousOutputs);
            }
        }

        // Return merged inputs with resolved action inputs taking priority
        return array_merge($previousOutputs, $resolvedInputs);
    }

    /**
     * Interpolate placeholders within a string using previous outputs
     *
     * @param  array<string, mixed>  $previousOutputs
     */
    private function interpolateString(string $string, array $previousOutputs): string
    {
        // Handle both single {key} and double {{key}} placeholders
        return preg_replace_callback('/\{\{?(\w+)\}?\}/', function ($matches) use ($previousOutputs) {
            $placeholderKey = $matches[1];

            if (array_key_exists($placeholderKey, $previousOutputs)) {
                return (string) $previousOutputs[$placeholderKey];
            }

            // Keep the placeholder as-is if not found in previous outputs
            return $matches[0];
        }, $string);
    }
}
