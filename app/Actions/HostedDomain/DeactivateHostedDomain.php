<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Models\HostedDomain;
use Illuminate\Validation\ValidationException;

class DeactivateHostedDomain
{
    public function deactivate(HostedDomain $hostedDomain): void
    {
        if ($hostedDomain->type === HostedDomainType::PRIMARY) {
            throw ValidationException::withMessages([
                'domain' => ['Cannot deactivate the primary domain.'],
            ]);
        }

        if ($hostedDomain->status->isProcessing()) {
            throw ValidationException::withMessages([
                'domain' => ['Cannot deactivate a domain while it is '.$hostedDomain->status->value.'.'],
            ]);
        }

        $hostedDomain->error = null;
        $hostedDomain->status = HostedDomainStatus::INACTIVE;
        $hostedDomain->save();

        $hostedDomain->site->webserver()->updateVHost($hostedDomain->site);
    }
}
