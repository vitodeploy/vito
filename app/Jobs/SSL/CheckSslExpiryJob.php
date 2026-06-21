<?php

namespace App\Jobs\SSL;

use App\Actions\SSL\CertificateParser;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Facades\Notifier;
use App\Models\Server;
use App\Models\Ssl;
use App\Notifications\SslCertificateExpiring;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckSslExpiryJob implements ShouldQueue
{
    use Queueable;

    private const EXPIRY_WARNING_DAYS = 14;

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $ssls = Ssl::query()
            ->with('site.server')
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
        } catch (Throwable $e) {
            Log::warning('[SSL expiry check] Failed to read certificate', [
                'ssl_id' => $ssl->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $dirty = false;

        if (! $ssl->expires_at?->equalTo($parsed['expires_at'])) {
            $ssl->expires_at = $parsed['expires_at'];
            $dirty = true;
        }

        if ($ssl->domains !== $parsed['domains']) {
            $ssl->domains = $parsed['domains'];
            $dirty = true;
        }

        $dirty = $this->handleExpiryNotification($ssl) || $dirty;

        if ($dirty) {
            $ssl->save();
        }
    }

    private function handleExpiryNotification(Ssl $ssl): bool
    {
        if ($ssl->expires_at === null) {
            return false;
        }

        if ($ssl->expires_at->isAfter(now()->addDays(self::EXPIRY_WARNING_DAYS))) {
            if ($ssl->expiry_notified_at !== null) {
                $ssl->expiry_notified_at = null;

                return true;
            }

            return false;
        }

        if ($ssl->expiry_notified_at !== null) {
            return false;
        }

        $server = $ssl->site?->server;

        if ($server === null) {
            return false;
        }

        Notifier::send($server, new SslCertificateExpiring($ssl));
        $ssl->expiry_notified_at = now();

        return true;
    }
}
