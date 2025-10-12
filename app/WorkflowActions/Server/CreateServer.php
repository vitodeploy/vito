<?php

namespace App\WorkflowActions\Server;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\WorkflowActions\AbstractWorkflowAction;

class CreateServer extends AbstractWorkflowAction
{
    public function form(): ?DynamicForm
    {
        return DynamicForm::make([
            DynamicField::make('name')
                ->text()
                ->label('Server Name'),
            DynamicField::make('server_provider')
                ->component()
                ->label('Server Provider'),
            DynamicField::make('plan')
                ->component()
                ->label('Plan'),
            DynamicField::make('region')
                ->component()
                ->label('Region'),
            DynamicField::make('os')
                ->select()
                ->options(config('core.operating_systems'))
                ->label('Operating System'),
        ]);
    }

    public function outputs(): array
    {
        return [
            'server_id' => 'The ID of the created server',
            'server_ip' => 'The IP address of the created server',
            'server_status' => 'The status of the created server',
        ];
    }

    public function run(array $input): array
    {
        $server = app(\App\Actions\Server\CreateServer::class)->create(
            $this->user,
            $this->workflow->project,
            $input,
            'sync',
        );

        return [
            'server_id' => $server->id,
            'server_ip' => $server->ip,
            'server_status' => $server->status->value,
        ];
    }
}
