<?php

namespace App\WorkflowActions\Service;

use App\DTOs\DynamicForm;
use App\WorkflowActions\AbstractWorkflowAction;

class InstallService extends AbstractWorkflowAction
{
    public function form(): ?DynamicForm
    {
        return DynamicForm::make([

        ]);
    }

    public function outputs(): array
    {
        return [
            'service_id' => 'The ID of the installed service',
            'service_status' => 'The status of the installed service',
        ];
    }

    public function run(array $input): array
    {
        return [];
    }
}
