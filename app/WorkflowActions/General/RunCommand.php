<?php

namespace App\WorkflowActions\General;

use App\Models\Server;
use App\WorkflowActions\AbstractWorkflowAction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RunCommand extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'server_id' => 'The ID of the server to run the command on',
            'command' => 'The command to run on the server',
            'user' => 'The user to run the command as',
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

        $server->ssh($input['user'])->exec($input['command']);

        return [];
    }
}
