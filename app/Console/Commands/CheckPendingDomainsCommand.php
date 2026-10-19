<?php

namespace App\Console\Commands;

use App\Enums\HostedDomainStatus;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use Illuminate\Console\Command;

class CheckPendingDomainsCommand extends Command
{
    protected $signature = 'domains:check-pending';

    protected $description = 'Verify pending site and environment domains';

    public function handle(): void
    {
        HostedDomain::query()
            ->where('status', HostedDomainStatus::PENDING)
            ->where('updated_at', '>=', now()->subHours(24))
            ->cursor()
            ->each(function (HostedDomain $domain) {
                dispatch(new CheckDomainJob($domain))->onQueue('ssh');
            });

        \App\Models\EnvironmentDomain::query()->with('component')
            ->where('status', HostedDomainStatus::PENDING)
            ->where('updated_at', '>=', now()->subHours(24))
            ->lazy()->each(function (\App\Models\EnvironmentDomain $domain): void {
                \App\Jobs\EnvironmentDomain\CheckEnvironmentDomainJob::dispatch($domain->id, $domain->component->server_id);
            });

        \App\Models\Ssl::query()->with('server')->whereNotNull('environment_id')->doesntHave('environmentDomains')
            ->lazy()->each(fn (\App\Models\Ssl $ssl) => app(\App\Actions\EnvironmentDomain\DeleteEnvironmentDomain::class)->queueUnusedCertificate($ssl));
    }
}
