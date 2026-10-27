<?php

namespace App\Actions\EnvironmentDomain;

use App\Actions\Component\SyncComponentVhost;
use App\Enums\HostedDomainStatus;
use App\Jobs\EnvironmentDomain\CheckEnvironmentDomainJob;
use App\Models\EnvironmentDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateEnvironmentDomain
{
    /** @param array<string, mixed> $input */
    public function update(EnvironmentDomain $domain, array $input): EnvironmentDomain
    {
        return app(ValidateEnvironmentDomain::class)->run($domain->environment, function () use ($domain, $input): EnvironmentDomain {
            $domain->refresh();
            if ($domain->status->isProcessing()) {
                throw ValidationException::withMessages(['host' => 'Wait for certificate provisioning to finish before editing this domain.']);
            }
            $previous = $domain->component;
            $ssl = $domain->ssl;
            app(ValidateEnvironmentDomain::class)->validate($domain->environment, $input, $domain);
            if ($domain->isDirty(['host', 'component_id', 'type', 'redirect_to_domain_id', 'ssl_method', 'ssl_id'])) {
                $domain->status = HostedDomainStatus::PENDING;
                $domain->error = null;
            }
            $synchronized = false;
            try {
                DB::transaction(function () use ($domain, $previous, &$synchronized): void {
                    $domain->save();
                    app(SyncComponentVhost::class)->sync($previous);
                    $synchronized = true;
                    if ($previous->id !== $domain->component_id) {
                        app(SyncComponentVhost::class)->sync($domain->component);
                    }
                });
            } catch (Throwable $e) {
                if ($synchronized) {
                    app(SyncComponentVhost::class)->sync($previous->refresh());
                }
                throw $e;
            }
            app(DeleteEnvironmentDomain::class)->queueUnusedCertificate($ssl);
            app(BroadcastEnvironmentDomainUpdate::class)->broadcast($domain);
            if ($domain->status === HostedDomainStatus::PENDING) {
                CheckEnvironmentDomainJob::dispatch($domain->id, $domain->component->server_id)->afterCommit();
            }

            return $domain;
        });
    }
}
