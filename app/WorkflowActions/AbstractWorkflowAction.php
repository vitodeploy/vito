<?php

namespace App\WorkflowActions;

use App\DTOs\DynamicForm;
use App\Models\User;
use App\Models\Workflow;

abstract class AbstractWorkflowAction implements WorkflowActionInterface
{
    /**
     * @param  User  $user  The user who runs the workflow.
     */
    public function __construct(
        protected readonly User $user,
        protected readonly Workflow $workflow
    ) {}

    public function form(): ?DynamicForm
    {
        return null;
    }
}
