<?php

namespace App\Actions\Site;

use App\Models\Site;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ChangePHPVersion
{
    /**
     * @throws ValidationException
     */
    public function handle(Site $site, array $input): void
    {
        $this->validate($site, $input);

        $site->changePHPVersion($input['php_version']);
    }

    /**
     * @throws ValidationException
     */
    protected function validate(Site $site, array $input): void
    {
        Validator::make($input, [
            'php_version' => 'required|in:'.implode(',', $site->server->installedPHPVersions()),
        ])->validateWithBag('changePHPVersion');
    }
}
