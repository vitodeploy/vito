<?php

namespace App\Actions\Site;

use App\Models\Site;
use App\SSH\Services\Webserver\Webserver;
use App\ValidationRules\DomainRule;

class UpdateDomain
{
    public function update(Site $site, array $input): void
    {
        $site->domain = $input['domain'];

        /** @var Webserver $webserver */
        $webserver = $site->server->webserver()->handler();
        $webserver->updateVHost($site);

        $site->save();
    }

    public static function rules(): array
    {
        return [
            'domain' => [
                new DomainRule,
            ],
        ];
    }
}
