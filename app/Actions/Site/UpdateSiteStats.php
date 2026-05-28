<?php

namespace App\Actions\Site;

use App\Jobs\Site\CleanupSiteStatsJob;
use App\Jobs\Site\WriteSiteStatsConfJob;
use App\Models\Site;

class UpdateSiteStats
{
    public function disable(Site $site): void
    {
        $site->jsonUpdate('type_data', 'stats_disabled', true);

        if ($site->server->service('log_analysis')) {
            dispatch(new CleanupSiteStatsJob($site->server, $site->id));
        }
    }

    public function enable(Site $site): void
    {
        $site->jsonForget('type_data', 'stats_disabled');

        if ($site->server->service('log_analysis')) {
            dispatch(new WriteSiteStatsConfJob($site));
        }
    }
}
