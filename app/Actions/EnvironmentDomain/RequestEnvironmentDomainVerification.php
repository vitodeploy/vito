<?php

namespace App\Actions\EnvironmentDomain;

use App\Enums\HostedDomainStatus;
use App\Jobs\EnvironmentDomain\CheckEnvironmentDomainJob;
use App\Models\EnvironmentDomain;
use Illuminate\Validation\ValidationException;

class RequestEnvironmentDomainVerification
{
    public function verify(EnvironmentDomain $domain): void
    {
        app(ValidateEnvironmentDomain::class)->run($domain->environment, function () use ($domain): void {
            $domain->refresh();
            if ($domain->status !== HostedDomainStatus::PENDING) {
                throw ValidationException::withMessages(['host' => 'Only pending domains can be verified.']);
            }
            $domain->error = null;
            $domain->touch();
            app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
            CheckEnvironmentDomainJob::dispatch($domain->id, $domain->component->server_id)->afterCommit();
        });
    }
}
