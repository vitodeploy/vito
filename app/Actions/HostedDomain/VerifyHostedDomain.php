<?php

namespace App\Actions\HostedDomain;

use App\Actions\Site\EnsureSiteVerificationKey;
use App\DTOs\VerificationResult;
use App\Models\HostedDomain;
use App\Models\Site;

class VerifyHostedDomain
{
    public function verify(HostedDomain $hostedDomain): VerificationResult
    {
        $site = $hostedDomain->site;
        if (! $site instanceof Site) {
            return VerificationResult::failure('Site not found for hosted domain.');
        }

        return app(VerifyDomain::class)->verify(
            $site->server, $hostedDomain->domain, app(EnsureSiteVerificationKey::class)->ensure($site), (string) $site->id, $site->id,
        );
    }
}
