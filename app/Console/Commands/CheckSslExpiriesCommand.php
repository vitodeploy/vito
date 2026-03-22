<?php

namespace App\Console\Commands;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\CheckSslExpiryJob;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Console\Command;

class CheckSslExpiriesCommand extends Command
{
    protected $signature = 'ssl:check-expiry';

    protected $description = 'Check and sync expiry dates for site-level Let\'s Encrypt certificates';

    public function handle(): void
    {
        $serverIds = Ssl::query()
            ->whereNotNull('ssls.site_id')
            ->where('ssls.type', SslType::LETSENCRYPT)
            ->where('ssls.status', SslStatus::CREATED)
            ->whereNotNull('ssls.certificate_path')
            ->join('sites', 'ssls.site_id', '=', 'sites.id')
            ->select('sites.server_id')
            ->distinct()
            ->pluck('server_id');

        $serverIds->each(function (int $serverId) {
            dispatch(new CheckSslExpiryJob(
                Server::findOrFail($serverId)
            ))->onQueue('ssh');
        });
    }
}
