<?php

namespace App\Actions\Site;

use App\Models\DeploymentScript;
use Illuminate\Support\Facades\Validator;

class UpdateDeploymentScript
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(DeploymentScript $deploymentScript, array $input): void
    {
        Validator::make($input, [
            'script' => ['required', 'string'],
        ])->validate();

        $deploymentScript->content = $input['script'];
        $deploymentScript->jsonUpdate('configs', 'restart_workers', $input['restart_workers'] ?? false, false);
        $deploymentScript->save();
    }
}
