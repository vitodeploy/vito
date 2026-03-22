<?php

namespace App\Actions\SSL;

use App\Enums\HostedDomainStatus;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\HostedDomain\SetupHostedDomainSslJob;
use App\Models\Site;
use Illuminate\Validation\ValidationException;

class RenewSiteSsl
{
    /**
     * @throws ValidationException
     */
    public function renew(Site $site): void
    {
        if (! $site->webserver()->createsSiteSSLs()) {
            throw ValidationException::withMessages([
                'ssl' => 'This webserver does not manage site-level SSL certificates.',
            ]);
        }

        $ssl = $site->ssls()
            ->where('type', SslType::LETSENCRYPT)
            ->where('status', SslStatus::CREATED)
            ->first();

        if (! $ssl) {
            throw ValidationException::withMessages([
                'ssl' => 'No active site-level SSL certificate found.',
            ]);
        }

        $hasUpdating = $site->hostedDomains()
            ->where('ssl_method', SslMethod::LETSENCRYPT)
            ->where('status', HostedDomainStatus::UPDATING)
            ->whereNotNull('ssl_id')
            ->exists();

        if ($hasUpdating) {
            throw ValidationException::withMessages([
                'ssl' => 'Unable to force renew SSL while a domain is updating.',
            ]);
        }

        $activeDomain = $site->hostedDomains()
            ->where('ssl_method', SslMethod::LETSENCRYPT)
            ->where('status', HostedDomainStatus::ACTIVE)
            ->whereNotNull('ssl_id')
            ->first();

        if (! $activeDomain) {
            throw ValidationException::withMessages([
                'ssl' => 'No active domains with site-level SSL found.',
            ]);
        }

        $ssl->status = SslStatus::CREATING;
        $ssl->save();

        $site->hostedDomains()
            ->where('ssl_method', SslMethod::LETSENCRYPT)
            ->where('status', HostedDomainStatus::ACTIVE)
            ->whereNotNull('ssl_id')
            ->update(['status' => HostedDomainStatus::UPDATING]);

        dispatch(new SetupHostedDomainSslJob($activeDomain))->onQueue('ssh');
    }
}
