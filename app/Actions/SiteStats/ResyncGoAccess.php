<?php

namespace App\Actions\SiteStats;

use App\Jobs\Site\ResyncGoAccessJob;
use App\Models\Server;

class ResyncGoAccess
{
    public function handle(Server $server): void
    {
        dispatch(new ResyncGoAccessJob($server));
    }
}
