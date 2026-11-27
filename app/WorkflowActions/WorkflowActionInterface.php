<?php

namespace App\WorkflowActions;

use App\DTOs\DynamicField;

interface WorkflowActionInterface
{
    /**
     * Either a list of DynamicField definitions or the legacy `name => description` map.
     *
     * @return array<int, DynamicField>|array<string, mixed>
     */
    public function inputs(): array;

    public function outputs(): array;

    public function run(array $input): array;
}
