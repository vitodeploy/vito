<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainType;
use App\Models\HostedDomain;
use Illuminate\Validation\ValidationException;

class DeleteHostedDomain
{
    public function delete(HostedDomain $hostedDomain): void
    {
        if ($hostedDomain->type === HostedDomainType::PRIMARY) {
            throw ValidationException::withMessages([
                'domain' => ['Cannot delete the primary domain.'],
            ]);
        }

        $hostedDomain->delete();
    }
}
