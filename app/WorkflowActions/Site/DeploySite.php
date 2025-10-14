<?php

namespace App\WorkflowActions\Site;

use App\Actions\Site\Deploy;
use App\Models\Site;
use App\WorkflowActions\AbstractWorkflowAction;
use Illuminate\Support\Facades\Validator;

class DeploySite extends AbstractWorkflowAction
{
    public function inputs(): array
    {
        return [
            'site_id' => 'The ID of the site',
        ];
    }

    public function outputs(): array
    {
        return [
            'site_id' => 'The ID of the site',
            'deployment_id' => 'The ID of the deployment',
            'deployment_status' => 'The status of the deployment',
        ];
    }

    public function run(array $input): array
    {
        Validator::make($input, [
            'site_id' => ['required', 'integer', 'exists:sites,id'],
        ])->validate();

        /** @var Site $site */
        $site = Site::findOrFail($input['site_id']);

        $this->authorize('update', [$site, $site->server]);

        $deployment = app(Deploy::class)->run($site);

        return [
            'site_id' => $site->id,
            'deployment_id' => $deployment->id,
            'deployment_status' => $deployment->status->value,
        ];
    }
}
