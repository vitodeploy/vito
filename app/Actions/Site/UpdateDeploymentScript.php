<?php

namespace App\Actions\Site;

use App\Models\DeploymentScript;
use App\Models\Site;
use Illuminate\Support\Facades\Validator;

class UpdateDeploymentScript
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        Validator::make($input, self::rules())->validate();

        /** @var DeploymentScript $script */
        $script = $site->deploymentScript;
        $script->content = $input['script'];
        $script->jsonUpdate('configs', 'restart_workers', $input['restart_workers'] ?? false, false);
        $script->save();
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [
            'script' => ['required', 'string'],
        ];
    }
}
