<?php

namespace App\WorkflowActions;

interface WorkflowActionInterface
{
    public function inputs(): array;

    public function outputs(): array;

    /**
     * This method will be executed when the action is triggered.
     *
     * @param  array  $input  The input data for the action.
     * @return array The result of the action execution which will be fed to the next action.
     */
    public function run(array $input): array;
}
