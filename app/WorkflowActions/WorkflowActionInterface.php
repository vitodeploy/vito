<?php

namespace App\WorkflowActions;

interface WorkflowActionInterface
{
    public function inputs(): array;

    public function outputs(): array;

    /**
     * @param  array  $input
     * @return array
     */
    public function run(array $input): array;
}
