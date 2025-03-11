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
        $service = $site->server->webserver();
        if (! $service) {
            throw new \RuntimeException('Webserver service not found');
        }

        /** @var Webserver $webserverHandler */
        $webserverHandler = $service->handler();
        $webserverHandler->deleteSite($site);

        if ($site->isIsolated()) {
            $phpService = $site->server->php();
            if (! $phpService) {
                throw new \RuntimeException('PHP service not found');
            }
            /** @var PHP $php */
            $php = $phpService->handler();
            $php->removeFpmPool($site->user, $site->php_version, $site->id);

            $os = $site->server->os();
            $os->deleteIsolatedUser($site->user);
        }

        $site->delete();
    }
}
