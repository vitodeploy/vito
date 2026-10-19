<?php

namespace App\Actions\SSL;

use App\Actions\EnvironmentDomain\ActivateEnvironmentDomain;
use App\Actions\EnvironmentDomain\BroadcastEnvironmentDomainUpdate;
use App\Enums\EnvironmentStatus;
use App\Enums\SslStatus;
use App\Models\Ssl;

class RenewEnvironmentSsl
{
    public function renew(int $sslId): void
    {
        $ssl = Ssl::query()->find($sslId);
        if (! $ssl || $ssl->environment?->status !== EnvironmentStatus::READY
            || $ssl->environment->application->status !== \App\Enums\ApplicationStatus::READY || ! $ssl->environmentDomains()->exists()
            || $ssl->type !== \App\Enums\SslType::LETSENCRYPT->value || $ssl->status !== SslStatus::CREATED
            || $ssl->is_wildcard || $ssl->expires_at?->isAfter(now()->addDays(30))) {
            return;
        }
        app(ActivateEnvironmentDomain::class)->issue($ssl);
        app(ActivateEnvironmentDomain::class)->complete($ssl);
    }

    public function failed(int $sslId): void
    {
        $ssl = Ssl::query()->find($sslId);
        foreach ($ssl?->environmentDomains()->get() ?? [] as $domain) {
            $domain->error = 'Certificate renewal failed. Check DNS and server connectivity, then retry.';
            $domain->save();
            app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
        }
    }
}
