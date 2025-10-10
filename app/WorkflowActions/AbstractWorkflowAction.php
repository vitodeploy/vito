<?php

namespace App\WorkflowActions;

use App\DTOs\DynamicForm;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;

abstract class AbstractWorkflowAction implements WorkflowActionInterface
{
    /**
     * @param  User  $user  The user who initiated the workflow.
     * @param  Project  $project  The project context for the workflow.
     */
    public function __construct(
        protected readonly Workflow $workflow
    ) {}

    public function form(): ?DynamicForm
    {
        return null;
    }
}
