<?php

namespace App\WorkflowActions\Site;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\WorkflowActions\AbstractWorkflowAction;

class CreateSite extends AbstractWorkflowAction
{
    public function form(): ?DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('server_id')
                ->label('Server ID')
                ->text(),
        ]);
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
