<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Site;
use App\SSH\Services\PHP\PHP;
use App\SSH\Services\Webserver\Webserver;

class DeleteSite
{
    /**
     * @throws SSHError
     */
    public function delete(Site $site): void
    {
        /** @var Webserver $webserverHandler */
        $webserverHandler = $site->server->webserver()->handler();
        $webserverHandler->deleteSite($site);

        if ($site->isIsolated()) {
            /** @var PHP $php */
            $php = $site->server->php()->handler();
            $php->removeFpmPool($site->user, $site->php_version, $site->id);

            $os = $site->server->os();
            $os->deleteIsolatedUser($site->user);
        }

        $site->delete();
    }
}
