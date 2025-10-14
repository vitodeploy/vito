<?php

namespace App\WorkflowActions\Server;

use App\WorkflowActions\AbstractWorkflowAction;

class CreateServer extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'name' => 'The name of the server',
            'provider' => 'The server provider. example: hetzner, digitalocean, aws, vultr, linode',
            'server_provider' => 'The server provider ID',
            'plan' => 'The server plan',
            'region' => 'The server region',
            'os' => __('Operating System (:os)', ['os' => implode(', ', config('core.operating_systems'))]),
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
