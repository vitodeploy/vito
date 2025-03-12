<?php

namespace App\Actions\Site;

use App\Exceptions\SSHError;
use App\Models\Service;
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
        /** @var Service $service */
        $service = $site->server->webserver();

        /** @var Webserver $webserverHandler */
        $webserverHandler = $service->handler();
        $webserverHandler->deleteSite($site);

        if ($site->isIsolated()) {
            /** @var Service $phpService */
            $phpService = $site->server->php();
            /** @var PHP $php */
            $php = $phpService->handler();
            $php->removeFpmPool($site->user, $site->php_version, $site->id);

            $os = $site->server->os();
            $os->deleteIsolatedUser($site->user);
        }

        $site->delete();
    }
}
