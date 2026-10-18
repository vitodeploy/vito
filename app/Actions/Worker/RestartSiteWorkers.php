<?php

namespace App\Actions\Worker;

use App\Actions\Site\BroadcastSiteUpdate;
use App\Models\ServerLog;
use App\Models\Site;

class RestartSiteWorkers
{
    public function restart(Site $site, ?ServerLog $log = null): void
    {
        $workers = $site->loadMissing('workers.site')->workers;
        if ($workers->isEmpty()) {
            return;
        }

        app(RestartWorkers::class)->restart($site->server, $workers, $log, $site->id);
        app(BroadcastSiteUpdate::class)->broadcast($site);
    }
}
