<?php

namespace App\Listeners;

use App\Events\SiteCreatedEvent;
use App\Jobs\Site\WriteSiteStatsConfJob;

class HandleSiteCreatedStats
{
    public function handle(SiteCreatedEvent $event): void
    {
        if ($event->site->server->service('log_analysis')) {
            dispatch(new WriteSiteStatsConfJob($event->site));
        }
    }
}
