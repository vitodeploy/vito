<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainStatus;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;

class ReactivateHostedDomain
{
    public function reactivate(HostedDomain $hostedDomain): void
    {
        $hostedDomain->status = HostedDomainStatus::PENDING;
        $hostedDomain->save();

        dispatch(new CheckDomainJob($hostedDomain))->onQueue('ssh');
    }
}
