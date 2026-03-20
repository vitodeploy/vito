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

        $hostedDomain->status = HostedDomainStatus::INACTIVE;
        $hostedDomain->save();
    }
}
