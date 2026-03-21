<?php

namespace App\Actions\Site;

use App\Models\Site;
use App\ValidationRules\DomainRule;
use Illuminate\Support\Facades\Validator;

class UpdateAliases
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $this->validate($input);

        $site->aliases = $input['aliases'] ?? [];

        $site->webserver()->updateVHost($site);

        $site->save();
    }

    protected function validate(array $input): void
    {
        Validator::make($input, [
            'aliases.*' => [
                new DomainRule,
            ],
        ])->validate();
    }
}
