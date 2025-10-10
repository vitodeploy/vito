<?php

namespace App\WorkflowActions;

use App\DTOs\DynamicForm;

interface WorkflowActionInterface
{
    public function form(): ?DynamicForm;

    public function outputs(): array;

    /**
     * This method will be executed when the action is triggered.
     *
     * @param  array  $input  The input data for the action.
     * @return array The result of the action execution which will be fed to the next action.
     */
    public function run(array $input): array;
}
