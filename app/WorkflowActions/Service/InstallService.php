<?php

namespace App\WorkflowActions\Service;

use App\Actions\Service\Install;
use App\Models\Server;
use App\WorkflowActions\AbstractWorkflowAction;

class InstallService extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'server_id' => 'The ID of the server to install the service on',
            'name' => 'The name of the service to install',
            'version' => 'The version of the service to install',
        ];
    }

    public function outputs(): array
    {
        return [
            'server_id' => 'The ID of the server the service was installed on',
            'service_id' => 'The ID of the installed service',
            'service_status' => 'The status of the installed service',
        ];
    }

    public function run(array $input): array
    {
        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);

        $this->authorize('update', $server);

        $service = app(Install::class)->install(
            $server,
            $input,
        );

        return [
            'server_id' => $server->id,
            'service_id' => $service->id,
            'service_status' => $service->status->value,
        ];
    }
}
