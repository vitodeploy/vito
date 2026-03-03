<?php

namespace App\WorkflowActions\Site;

use App\Models\Server;
use App\Models\Site;
use App\WorkflowActions\AbstractWorkflowAction;
use Illuminate\Support\Facades\Validator;

abstract class CreateSite extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'server_id' => 'The ID of the server to create the site on',
            'domain' => 'The domain of the site (example.com)',
            'aliases' => [
                'alias-1',
                'alias-2',
                'send this field empty [] if you do not want to set any aliases',
            ],
            'user' => 'Isolated user, remove this field to use the default user (vito)',
        ];
    }

    public function outputs(): array
    {
        return [
            'site_id' => 'The ID of the created site',
            'site_domain' => 'The domain of the created site',
            'site_path' => 'The path of the created site on the server',
            'site_status' => 'The status of the created site',
        ];
    }

    public function run(array $input): array
    {
        Validator::make($input, [
            'server_id' => ['required', 'integer', 'exists:servers,id'],
        ])->validate();

        /** @var Server $server */
        $server = Server::query()->findOrFail($input['server_id']);

        $this->authorize('create', [Site::class, $server]);

        $site = app(\App\Actions\Site\CreateSite::class)->create(
            $server,
            $input,
        );

        return [
            'site_id' => $site->id,
            'site_domain' => $site->domain,
            'site_path' => $site->path,
            'site_status' => $site->status->value,
        ];
    }
}
