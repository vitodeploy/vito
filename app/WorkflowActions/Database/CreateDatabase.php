<?php

namespace App\WorkflowActions\Database;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\WorkflowActions\AbstractWorkflowAction;

class CreateDatabase extends AbstractWorkflowAction
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
        ];
    }

    public function run(array $input): array
    {
        return [
        ];
    }
}
