<?php

namespace App\Listeners;

use App\Events\SiteDeletedEvent;
use App\Jobs\Site\CleanupSiteStatsJob;

class HandleSiteDeletedStats
{
    public function handle(SiteDeletedEvent $event): void
    {
        if ($event->server->service('log_analysis')) {
            dispatch(new CleanupSiteStatsJob($event->server, $event->siteId));
        }
    }
}
