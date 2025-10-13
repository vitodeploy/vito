<?php

namespace App\WorkflowActions\Site;

use App\WorkflowActions\AbstractWorkflowAction;

class CreateSite extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [];
    }

    public function outputs(): array
    {
        return [
            'site_id' => 'The ID of the created site',
            'site_status' => 'The status of the created site',
        ];
    }

    public function run(array $input): array
    {
        return [
        ];
    }
}
