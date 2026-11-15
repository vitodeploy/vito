<?php

namespace App\Console\Commands;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\CreateLetsEncryptWildcardSslJob;
use App\Jobs\SSL\RenewEnvironmentSslJob;
use App\Models\Ssl;
use Illuminate\Console\Command;

class RenewSslCertificatesCommand extends Command
{
    protected $signature = 'ssl:renew-wildcards';

    protected $description = 'Renew wildcard and environment Let\'s Encrypt SSL certificates expiring within 30 days';

    public function handle(): void
    {
        Ssl::query()
            ->with('server')
            ->where('type', SslType::LETSENCRYPT)
            ->where('is_wildcard', true)
            ->whereNotNull('server_id')
            ->whereNull('site_id')
            ->where('status', SslStatus::CREATED)
            ->where('expires_at', '<=', now()->addDays(30))
            ->lazy()
            ->each(function (Ssl $ssl) {
                dispatch(new CreateLetsEncryptWildcardSslJob($ssl->server, $ssl, isRenewal: true))
                    ->onQueue('ssh-certbot');
            });

        Ssl::query()->whereNotNull('environment_id')->whereNotNull('server_id')
            ->where('type', SslType::LETSENCRYPT)->where('is_wildcard', false)
            ->where('status', SslStatus::CREATED)->where('expires_at', '<=', now()->addDays(30))
            ->lazy()->each(function (Ssl $ssl): void {
                RenewEnvironmentSslJob::dispatch($ssl->id, $ssl->server_id);
            });
    }
}
