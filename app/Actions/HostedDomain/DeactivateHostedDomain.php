<?php

namespace App\Actions\HostedDomain;

use App\Actions\Site\BroadcastSiteUpdate;
use App\Enums\HostedDomainStatus;
use App\Models\HostedDomain;

class DeactivateHostedDomain
{
    public function deactivate(HostedDomain $hostedDomain): void
    {
        $hostedDomain->ensureModifiable('deactivate');

        $hostedDomain->error = null;
        $hostedDomain->status = HostedDomainStatus::INACTIVE;
        $hostedDomain->save();

        $hostedDomain->site->webserver()->updateVHost($hostedDomain->site);

        app(BroadcastSiteUpdate::class)->broadcast($hostedDomain->site);
    }
}
