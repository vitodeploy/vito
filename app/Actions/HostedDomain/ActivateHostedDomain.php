<?php

namespace App\Actions\HostedDomain;

use App\Enums\HostedDomainStatus;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\HostedDomain\SetupHostedDomainSslJob;
use App\Models\HostedDomain;
use App\Models\Site;

class ActivateHostedDomain
{
    public function activate(HostedDomain $hostedDomain): void
    {
        $site = $hostedDomain->site;

        match ($hostedDomain->ssl_method) {
            SslMethod::NONE => $this->activateWithoutSsl($hostedDomain, $site),
            SslMethod::CUSTOM => $this->activateWithCustomSsl($hostedDomain, $site),
            SslMethod::LETSENCRYPT => $this->activateWithLetsEncrypt($hostedDomain, $site),
        };
    }

    private function activateWithoutSsl(HostedDomain $hostedDomain, Site $site): void
    {
        $hostedDomain->error = null;
        $hostedDomain->status = HostedDomainStatus::ACTIVE;
        $hostedDomain->save();

        $site->webserver()->updateVHost($site);
    }

    private function activateWithCustomSsl(HostedDomain $hostedDomain, Site $site): void
    {
        if (! $hostedDomain->ssl_id || ! $hostedDomain->ssl) {
            $hostedDomain->error = 'No certificate provided';
            $hostedDomain->status = HostedDomainStatus::PENDING;
            $hostedDomain->save();

            return;
        }

        if (! $hostedDomain->ssl->coversDomain($hostedDomain->domain)) {
            $hostedDomain->error = 'Certificate does not support this domain';
            $hostedDomain->status = HostedDomainStatus::PENDING;
            $hostedDomain->save();

            return;
        }

        $hostedDomain->error = null;
        $hostedDomain->status = HostedDomainStatus::ACTIVE;
        $hostedDomain->save();

        $site->webserver()->updateVHost($site);
    }

    private function activateWithLetsEncrypt(HostedDomain $hostedDomain, Site $site): void
    {

        if (! $site->webserver()->createsSiteSSLs()) {
            $hostedDomain->error = null;
            $hostedDomain->status = HostedDomainStatus::ACTIVE;
            $hostedDomain->save();

            $site->webserver()->updateVHost($site);

            return;
        }

        $ssl = $site->ssls()
            ->where('type', SslType::LETSENCRYPT)
            ->where('status', SslStatus::CREATED)
            ->latest('expires_at')
            ->first();

        if ($ssl && $ssl->coversDomain($hostedDomain->domain)) {
            $hostedDomain->ssl_id = $ssl->id;
            $hostedDomain->error = null;
            $hostedDomain->status = HostedDomainStatus::ACTIVE;
            $hostedDomain->save();

            $site->webserver()->updateVHost($site);

            return;
        }

        $hostedDomain->error = null;
        $hostedDomain->status = HostedDomainStatus::UPDATING;
        $hostedDomain->save();

        dispatch(new SetupHostedDomainSslJob($hostedDomain))->onQueue('ssh');
    }
}
