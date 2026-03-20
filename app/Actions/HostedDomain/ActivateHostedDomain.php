<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainStatus;
use App\Models\HostedDomain;

class ActivateHostedDomain
{
    public function activate(HostedDomain $hostedDomain): void
    {
        $hostedDomain->status = HostedDomainStatus::ACTIVE;
        $hostedDomain->save();
    }
}
