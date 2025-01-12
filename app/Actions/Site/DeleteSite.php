<?php

namespace App\Actions\Site;

use App\Models\Site;
use App\SSH\OS\OS;
use App\SSH\Services\PHP\PHP;
use App\SSH\Services\Webserver\Webserver;

class DeleteSite
{
    public function delete(Site $site): void
    {
        /** @var Webserver $webserverHandler */
        $webserverHandler = $site->server->webserver()->handler();
        $webserverHandler->deleteSite($site);

        if ($site->is_isolated) {
            /** @var PHP $php */
            $php = $site->server->php();
            $php->removeFpmPool($site->isolated_username, $site->php_version, $site->id);

            $os = $site->server->os();
            $os->deleteIsolatedUser($site->isolated_username);
        }

        $site->delete();
    }
}
