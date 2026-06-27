<?php

namespace App\Actions\SSL;

use App\Facades\Notifier;
use App\Models\Ssl;
use App\Notifications\SslCertificateExpiring;
use Illuminate\Validation\ValidationException;

class CheckSslExpiry
{
    private const EXPIRY_WARNING_DAYS = 14;

    /**
     * Read the certificate from the server, refresh the stored expiry date and
     * domains, and optionally send the expiry notification.
     *
     * @throws ValidationException
     */
    public function check(Ssl $ssl, bool $notify = true, mixed $ssh = null): void
    {
        $server = $ssl->server ?? $ssl->site?->server;

        if ($server === null || $ssl->certificate_path === null) {
            throw ValidationException::withMessages([
                'ssl' => 'This certificate cannot be checked.',
            ]);
        }

        $ssh ??= $server->ssh();

        $certificate = trim($ssh->exec('sudo cat '.escapeshellarg($ssl->certificate_path)));

        if (empty($certificate) || ! str_contains($certificate, 'BEGIN CERTIFICATE')) {
            throw ValidationException::withMessages([
                'ssl' => 'Could not read the certificate from the server.',
            ]);
        }

        $parsed = CertificateParser::parse($certificate);

        $dirty = false;

        if (! $ssl->expires_at?->equalTo($parsed['expires_at'])) {
            $ssl->expires_at = $parsed['expires_at'];
            $dirty = true;
        }

        if ($ssl->domains !== $parsed['domains']) {
            $ssl->domains = $parsed['domains'];
            $dirty = true;
        }

        if ($notify) {
            $dirty = $this->handleExpiryNotification($ssl) || $dirty;
        }

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

        $server = $ssl->server ?? $ssl->site?->server;

        if ($server === null) {
            return false;
        }

        Notifier::send($server, new SslCertificateExpiring($ssl));
        $ssl->expiry_notified_at = now();

        return true;
    }
}
