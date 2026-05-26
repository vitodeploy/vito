<?php

namespace App\Listeners;

use App\Events\SiteDeletedEvent;
use App\Jobs\Site\CleanupSiteStatsJob;
use App\Models\Server;

class HandleSiteDeletedStats
{
    public function handle(SiteDeletedEvent $event): void
    {
        $server = Server::find($event->serverId);

        if ($server && $server->service('log_analysis')) {
            dispatch(new CleanupSiteStatsJob($server, $event->siteId));
        }
    }
}
