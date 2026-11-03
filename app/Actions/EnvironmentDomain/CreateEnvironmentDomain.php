<?php

namespace App\Actions\EnvironmentDomain;

use App\Actions\Component\SyncComponentVhost;
use App\Enums\HostedDomainStatus;
use App\Jobs\EnvironmentDomain\CheckEnvironmentDomainJob;
use App\Models\Environment;
use App\Models\EnvironmentDomain;

class CreateEnvironmentDomain
{
    private const int DNS_PROPAGATION_SECONDS = 30;

    /** @param array<string, mixed> $input */
    public function create(Environment $environment, array $input): EnvironmentDomain
    {
        return app(ValidateEnvironmentDomain::class)->run($environment, function () use ($environment, $input): EnvironmentDomain {
            $domain = app(ValidateEnvironmentDomain::class)->validate($environment, $input);
            $domain->status = HostedDomainStatus::PENDING;
            $domain->save();
            if (! empty($input['dns_domain_id'])) {
                app(PointDomainAtServer::class)->point($domain, (int) $input['dns_domain_id']);
                $domain->save();
            }
            app(SyncComponentVhost::class)->sync($domain->component);
            app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
            CheckEnvironmentDomainJob::dispatch($domain->id, $domain->component->server_id)->delay($domain->dns_domain_id ? self::DNS_PROPAGATION_SECONDS : 0)->afterCommit();

            return $domain;
        });
    }
}
