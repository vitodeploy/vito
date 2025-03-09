<?php

namespace App\Actions\Site;

use App\Models\Site;

class UpdateDeploymentScript
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $script = $site->deploymentScript;
        $script->content = $input['script'];
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
