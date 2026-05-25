<?php

namespace App\Actions\HostedDomain;

use App\Actions\Site\BroadcastSiteUpdate;
use App\Enums\HostedDomainStatus;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use Illuminate\Validation\ValidationException;

class ReactivateHostedDomain
{
    public function reactivate(HostedDomain $hostedDomain): void
    {
        if ($hostedDomain->status !== HostedDomainStatus::INACTIVE) {
            throw ValidationException::withMessages([
                'domain' => ['Can only reactivate an inactive domain.'],
            ]);
        }

        $hostedDomain->status = HostedDomainStatus::UPDATING;
        $hostedDomain->save();

        dispatch(new CheckDomainJob($hostedDomain))->onQueue('ssh');

        app(BroadcastSiteUpdate::class)->broadcast($hostedDomain->site);
    }
}
