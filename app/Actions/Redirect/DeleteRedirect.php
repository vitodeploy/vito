<?php

namespace App\Actions\Redirect;

use App\Models\Redirect;
use App\Models\Service;
use App\Models\Site;
use App\SSH\Services\Webserver\Webserver;

class DeleteRedirect
{
    public function delete(Site $site, Redirect $redirect): void
    {
        $redirect->delete();

        /** @var Service $service */
        $service = $site->server->webserver();

        /** @var Webserver $webserver */
        $webserver = $service->handler();
        $webserver->updateVHost($site);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function rules(): array
    {
        return [];
    }
}
