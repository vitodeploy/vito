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

        $webserver = $site->webserver();
        $webserver->updateVHost($site, vhost: (string) $site->type()->vhost($webserver::id()), restart: false);

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
