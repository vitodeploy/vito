<?php

namespace App\Actions\Site;

use App\Models\Service;
use App\Models\Site;
use App\Services\Webserver\Webserver;
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

        /** @var Service $service */
        $service = $site->server->webserver();

        /** @var Webserver $webserver */
        $webserver = $service->handler();
        $webserver->updateVHost($site, regenerate: [
            'core',
        ]);

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
