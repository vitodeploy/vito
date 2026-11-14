<?php

namespace App\Actions\HostedDomain;

use App\Actions\Site\EnsureSiteVerificationKey;
use App\DTOs\VerificationResult;
use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Models\HostedDomain;
use App\Models\Site;
use Illuminate\Support\Facades\Log;
use Throwable;

class VerifyHostedDomain
{
    public function verify(HostedDomain $hostedDomain): VerificationResult
    {
        $site = $hostedDomain->site;
        if (! $site instanceof Site) {
            return VerificationResult::failure('Site not found for hosted domain.');
        }

        if (! $this->publishChallengeRoute($hostedDomain, $site)) {
            return VerificationResult::failure('Could not publish verification challenge on the web server.');
        }

        return app(VerifyDomain::class)->verify(
            $site->server, $hostedDomain->domain, app(EnsureSiteVerificationKey::class)->ensure($site), (string) $site->id, $site->id,
        );
    }

    /**
     * Rewrite the site's vhost when the domains it should serve have changed,
     * for example after a domain failed verification and went back to pending.
     *
     * Custom templates don't carry the challenge block, so they are left alone.
     * The web server is only touched when the generated vhost differs from the
     * deployed one, so the periodic pending-domain retry does not reload it.
     */
    public function syncVhost(Site $site): void
    {
        if ($site->vhost_template !== null || ! $site->vhost_generation_enabled) {
            return;
        }

        $webserver = $site->webserver();

        if (trim($webserver->getVHost($site)) === trim($webserver->generateVhost($site))) {
            return;
        }

        $webserver->updateVHost($site);
    }

    /**
     * A domain that is not active yet is not in the site's vhost, so the web
     * server would answer from another server block and never serve the
     * challenge. Regenerate the vhost, which adds a challenge-only block for it.
     */
    private function publishChallengeRoute(HostedDomain $hostedDomain, Site $site): bool
    {
        if ($hostedDomain->type === HostedDomainType::PRIMARY || $hostedDomain->status === HostedDomainStatus::ACTIVE) {
            return true;
        }

        try {
            $this->syncVhost($site);

            return true;
        } catch (Throwable) {
            Log::warning('hosted-domain verification: failed to publish challenge route', [
                'domain' => $hostedDomain->domain,
                'site_id' => $site->id,
            ]);

            return false;
        }
    }
}
