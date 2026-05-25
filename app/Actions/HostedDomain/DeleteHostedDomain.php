<?php

namespace App\Actions\HostedDomain;

use App\Actions\Site\BroadcastSiteUpdate;
use App\Models\HostedDomain;

class DeleteHostedDomain
{
    public function delete(HostedDomain $hostedDomain): void
    {
        $hostedDomain->ensureModifiable('delete');

        $site = $hostedDomain->site;

        $hostedDomain->delete();

        $site->webserver()->updateVHost($site);

        app(BroadcastSiteUpdate::class)->broadcast($site);
    }
}
