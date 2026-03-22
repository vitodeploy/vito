<?php

namespace App\Jobs\SSL;

use App\Actions\SSL\CertificateParser;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CheckSslExpiryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $ssls = Ssl::query()
            ->whereHas('site', fn ($q) => $q->where('server_id', $this->server->id))
            ->whereNotNull('site_id')
            ->where('type', SslType::LETSENCRYPT)
            ->where('status', SslStatus::CREATED)
            ->whereNotNull('certificate_path')
            ->get();

        if ($ssls->isEmpty()) {
            return;
        }

        $ssh = $this->server->ssh();

        foreach ($ssls as $ssl) {
            $this->checkCertificate($ssh, $ssl);
        }
    }

    private function checkCertificate(mixed $ssh, Ssl $ssl): void
    {
        try {
            $certificate = trim($ssh->exec("sudo cat {$ssl->certificate_path}"));

            if (empty($certificate) || ! str_contains($certificate, 'BEGIN CERTIFICATE')) {
                return;
            }

            $parsed = CertificateParser::parse($certificate);

            if ($ssl->expires_at?->equalTo($parsed['expires_at'])) {
                return;
            }

            $ssl->expires_at = $parsed['expires_at'];
            $ssl->domains = $parsed['domains'];
            $ssl->save();
        } catch (Throwable) {
            return;
        }
    }
}
