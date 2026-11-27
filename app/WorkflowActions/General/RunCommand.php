<?php

namespace App\WorkflowActions\General;

use App\DTOs\DynamicField;
use App\Models\Server;
use App\WorkflowActions\AbstractWorkflowAction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RunCommand extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            DynamicField::make('server_id')->resource('server')->label('Server'),
            DynamicField::make('command')->textarea()->label('Command')->className('font-mono'),
            DynamicField::make('user')->text()->label('User')->placeholder('vito')->description('Leave empty to run as the server\'s default user'),
        ];
    }

    public function outputs(): array
    {
        return [];
    }

    public function run(array $input): array
    {
        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);

        $this->authorize('update', $server);

        Validator::make($input, [
            'command' => ['required', 'string'],
            'user' => [
                'nullable',
                'string',
                Rule::in($server->getSshUsers()),
            ],
        ])->validate();

        $server->ssh(($input['user'] ?? null) ?: null)->exec($input['command']);

        return [];
    }
}
