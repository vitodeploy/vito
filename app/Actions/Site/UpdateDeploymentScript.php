<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateDeploymentScript
{
    /**
     * @throws ValidationException
     */
    public function update(Site $site, array $input): void
    {
        $this->validate($input);

        $site->deploymentScript()->update([
            'content' => $input['script'],
        ]);
    }

    /**
     * @throws ValidationException
     */
    protected function validate(array $input): void
    {
        Validator::make($input, [
            'script' => 'required',
        ]);
    }
}
