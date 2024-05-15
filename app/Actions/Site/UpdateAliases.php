<?php

namespace App\Actions\Site;

use App\Models\Site;
use App\SSH\Services\Webserver\Webserver;
use App\ValidationRules\DomainRule;
use Illuminate\Support\Facades\Validator;

class UpdateAliases
{
    public function update(Site $site, array $input): void
    {
        $this->validate($input);

        $site->aliases = $input['aliases'] ?? [];

        /** @var Webserver $webserver */
        $webserver = $site->server->webserver()->handler();
        $webserver->updateVHost($site, ! $site->hasSSL());

        $site->save();
    }

    private function validate(array $input): void
    {
        Validator::make($input, [
            'aliases.*' => [
                new DomainRule(),
            ],
        ])->validate();
    }
}
