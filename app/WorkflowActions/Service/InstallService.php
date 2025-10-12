<?php

namespace App\WorkflowActions\Service;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\Models\Server;
use App\WorkflowActions\AbstractWorkflowAction;

class InstallService extends AbstractWorkflowAction
{
    public function form(): ?DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('server_id')
                ->label('Server ID')
                ->component(),
            DynamicField::make('name')
                ->label('Service Name')
                ->select()
                ->options(array_keys(config('service.services'))),
            DynamicField::make('version')
                ->label('Service Version')
                ->text(),
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
        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);

        $service = app(\App\Actions\Service\Install::class)->install(
            $server,
            $input,
            'sync',
        );

        return [
            'service_id' => $service->id,
            'service_status' => $service->status->value,
        ];
    }
}
