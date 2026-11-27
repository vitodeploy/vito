<?php

namespace App\WorkflowActions\Server;

use App\DTOs\DynamicField;
use App\WorkflowActions\AbstractWorkflowAction;

class CreateServer extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            DynamicField::make('name')->text()->label('Name'),
            DynamicField::make('provider')->select()->label('Provider')->options(array_keys(config('server-provider.providers')))->half(),
            DynamicField::make('server_provider')->resource('server_provider')->label('Provider Profile')->half(),
            DynamicField::make('region')->text()->label('Region')->description('The region ID on the provider')->half(),
            DynamicField::make('plan')->text()->label('Plan')->description('The plan ID on the provider')->half(),
            DynamicField::make('os')->select()->label('Operating System')->options(config('core.operating_systems')),
            DynamicField::make('ip')->text()->label('IP Address')->description('Custom provider only')->half(),
            DynamicField::make('port')->text()->label('SSH Port')->placeholder('22')->description('Custom provider only')->half(),
            DynamicField::make('services_help')->alert()->description('To install services, switch to JSON mode and add a "services" list, e.g. [{"type": "webserver", "name": "nginx", "version": "latest"}, {"type": "php", "name": "php", "version": "8.4"}].'),
        ];
    }

    public function outputs(): array
    {
        return [
            'server_id' => 'The ID of the created server',
            'server_name' => 'The name of the created server',
            'server_ip' => 'The IP address of the created server',
            'server_public_key' => 'The public key of the created server',
            'server_provider_id' => 'The provider-specific ID of the created server',
            'server_provider' => 'The provider of the created server',
            'server_status' => 'The status of the created server',
        ];
    }

    public function run(array $input): array
    {
        $server = app(\App\Actions\Server\CreateServer::class)->create(
            $this->user,
            $this->workflow->project,
            $input,
        );

        return [
            'server_id' => $server->id,
            'server_ip' => $server->ip,
            'server_name' => $server->name,
            'server_public_key' => $server->public_key,
            'server_provider_id' => $server->provider_id,
            'server_provider' => $server->provider,
            'server_status' => $server->status->value,
        ];
    }
}
