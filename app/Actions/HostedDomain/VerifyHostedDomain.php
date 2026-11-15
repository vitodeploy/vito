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

    public function syncVhost(Site $site): void
    {
        if ($site->vhost_template !== null || ! $site->vhost_generation_enabled) {
            return;
        }

        $webserver = $site->webserver();
        $current = $webserver->getVHost($site);

        if (trim($current) === trim($webserver->generateVhost($site))) {
            return;
        }

        try {
            $webserver->updateVHost($site);
        } catch (Throwable $e) {
            if (trim($current) !== '') {
                rescue(fn () => $webserver->updateVHost($site, $current), report: false);
            }

            throw $e;
        }
    }

    private function publishChallengeRoute(HostedDomain $hostedDomain, Site $site): bool
    {
        if ($hostedDomain->type === HostedDomainType::PRIMARY || $hostedDomain->status === HostedDomainStatus::ACTIVE) {
            return true;
        }

        try {
            $this->syncVhost($site);

            return true;
        } catch (Throwable $e) {
            Log::warning('hosted-domain verification: failed to publish challenge route', [
                'domain' => $hostedDomain->domain,
                'site_id' => $site->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
