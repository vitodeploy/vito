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
        $site->deploymentScript()->update([
            'content' => $input['script'],
        ]);
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
