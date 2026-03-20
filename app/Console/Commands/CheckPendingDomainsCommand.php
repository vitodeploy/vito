<?php

namespace App\Console\Commands;

use App\Enums\HostedDomainStatus;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use Illuminate\Console\Command;

class CheckPendingDomainsCommand extends Command
{
    protected $signature = 'domains:check-pending';

    protected $description = 'Check DNS resolution for pending hosted domains';

    public function handle(): void
    {
        HostedDomain::query()
            ->where('status', HostedDomainStatus::PENDING)
            ->with('site')
            ->get()
            ->groupBy(fn (HostedDomain $hd) => $hd->site->server_id)
            ->each(function ($domains) {
                /** @var HostedDomain $domain */
                foreach ($domains as $domain) {
                    dispatch(new CheckDomainJob($domain))->onQueue('ssh');
                }
            });
    }
}
