<?php

namespace App\Actions\EnvironmentDomain;

use App\Actions\Component\SyncComponentVhost;
use App\Enums\HostedDomainStatus;
use App\Jobs\EnvironmentDomain\CheckEnvironmentDomainJob;
use App\Models\Environment;
use App\Models\EnvironmentDomain;

class CreateEnvironmentDomain
{
    /** @param array<string, mixed> $input */
    public function create(Environment $environment, array $input): EnvironmentDomain
    {
        return app(ValidateEnvironmentDomain::class)->run($environment, function () use ($environment, $input): EnvironmentDomain {
            $domain = app(ValidateEnvironmentDomain::class)->validate($environment, $input);
            $domain->status = HostedDomainStatus::PENDING;
            $domain->save();
            app(SyncComponentVhost::class)->sync($domain->component);
            app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
            CheckEnvironmentDomainJob::dispatch($domain->id, $domain->component->server_id)->afterCommit();

            return $domain;
        });
    }
}
