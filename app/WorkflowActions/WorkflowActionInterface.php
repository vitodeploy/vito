<?php

namespace App\WorkflowActions;

interface WorkflowActionInterface
{
    public function inputs(): array;

    public function outputs(): array;

    public function run(array $input): array;
}
