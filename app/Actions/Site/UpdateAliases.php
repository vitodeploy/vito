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

        /** @var Webserver $webserver */
        $webserver = $site->server->webserver()->handler();
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
