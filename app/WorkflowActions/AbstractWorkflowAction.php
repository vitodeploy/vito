<?php

namespace App\WorkflowActions;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Auth\Access\AuthorizationException;

abstract class AbstractWorkflowAction implements WorkflowActionInterface
{
    /**
     * @param  User  $user  The user who runs the workflow.
     */
    public function __construct(
        protected readonly User $user,
        protected readonly Workflow $workflow
    ) {}

    public function inputs(): array
    {
        return [];
    }

    public function authorize(string $action, mixed $arguments = []): void
    {
        if (! $this->user->can($action, $arguments)) {
            throw new AuthorizationException("User can't perform this action.");
        }
    }
}
