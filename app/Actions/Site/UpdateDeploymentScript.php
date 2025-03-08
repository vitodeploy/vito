<?php

namespace App\Actions\Site;

use App\Models\Site;

class UpdateDeploymentScript
{
    public function update(Site $site, array $input): void
    {
        $script = $site->deploymentScript;
        $script->content = $input['script'];
        $script->save();
    }

    public static function rules(): array
    {
        return [
            'script' => ['required', 'string'],
        ];
    }
}
