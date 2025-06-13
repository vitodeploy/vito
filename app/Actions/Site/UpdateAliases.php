<?php

namespace App\Actions\Site;

use App\Models\Service;
use App\Models\Site;
use App\Services\Webserver\Webserver;
use App\ValidationRules\DomainRule;

class UpdateAliases
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
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

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'aliases.*' => [
                new DomainRule,
            ],
        ];
    }
}
