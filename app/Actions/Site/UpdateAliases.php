<?php

namespace App\Actions\Site;

use App\Models\Site;
use App\SSH\Services\Webserver\Webserver;
use App\ValidationRules\DomainRule;

class UpdateAliases
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function update(Site $site, array $input): void
    {
        $site->aliases = $input['aliases'] ?? [];

        $service = $site->server->webserver();
        if (! $service) {
            throw new \RuntimeException('Webserver service not found');
        }

        /** @var Webserver $webserver */
        $webserver = $service->handler();
        $webserver->updateVHost($site);

        $site->save();
    }

    /**
     * @return array<string, array<mixed>>
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
