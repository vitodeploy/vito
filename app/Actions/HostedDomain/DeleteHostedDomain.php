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

        if ($hostedDomain->status->isProcessing()) {
            throw ValidationException::withMessages([
                'domain' => ['Cannot delete a domain while it is '.$hostedDomain->status->value.'.'],
            ]);
        }

        $site = $hostedDomain->site;

        $hostedDomain->delete();

        $site->webserver()->updateVHost($site);
    }
}
