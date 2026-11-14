<?php

namespace App\Console\Commands;

use App\Actions\EnvironmentDomain\DeleteEnvironmentDomain;
use App\Enums\HostedDomainStatus;
use App\Jobs\EnvironmentDomain\CheckEnvironmentDomainJob;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\EnvironmentDomain;
use App\Models\HostedDomain;
use App\Models\Ssl;
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

        EnvironmentDomain::query()->with('component')
            ->where('status', HostedDomainStatus::PENDING)
            ->where('updated_at', '>=', now()->subHours(24))
            ->lazy()->each(function (EnvironmentDomain $domain): void {
                CheckEnvironmentDomainJob::dispatch($domain->id, $domain->component->server_id);
            });

        Ssl::query()->with('server')->whereNotNull('environment_id')->doesntHave('environmentDomains')
            ->lazy()->each(fn (Ssl $ssl) => app(DeleteEnvironmentDomain::class)->queueUnusedCertificate($ssl));
    }
}
