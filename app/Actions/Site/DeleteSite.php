<?php

namespace App\Actions\Site;

use App\Models\Site;
use App\SSH\Services\Webserver\Webserver;

class DeleteSite
{
    public function delete(Site $site): void
    {
        /** @var Webserver $webserverHandler */
        $webserverHandler = $site->server->webserver()->handler();
        $webserverHandler->deleteSite($site);
        $site->delete();
    }
}
