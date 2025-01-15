<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Validation\ValidationException;

class UpdateDeploymentScript
{
    /**
     * @throws ValidationException
     */
    public function update(Site $site, array $input): void
    {
        $script = $site->deploymentScript;
        $script->content = $input['script'];
        $script->save();
    }

    /**
     * @throws ValidationException
     */
    public static function rules(): array
    {
        return [
            'script' => ['required', 'string'],
        ];
    }
}
