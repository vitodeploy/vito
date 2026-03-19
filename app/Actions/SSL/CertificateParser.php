<?php

namespace App\Actions\SSL;

use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class CertificateParser
{
    /**
     * Parse a PEM certificate to extract expiry date and domains from SANs.
     *
     * @return array{expires_at: Carbon, domains: array<int, string>}
     *
     * @throws ValidationException
     */
    public static function parse(string $certificate): array
    {
        $parsed = openssl_x509_parse($certificate);

        if ($parsed === false || ! isset($parsed['validTo_time_t'])) {
            throw ValidationException::withMessages([
                'certificate' => 'Could not parse the certificate. Please provide a valid PEM-encoded certificate.',
            ]);
        }

        $domains = [];
        $san = $parsed['extensions']['subjectAltName'] ?? '';
        foreach (explode(',', $san) as $entry) {
            $entry = trim($entry);
            if (str_starts_with($entry, 'DNS:')) {
                $domains[] = strtolower(substr($entry, 4));
            }
        }

        if (empty($domains) && isset($parsed['subject']['CN'])) {
            $domains[] = strtolower($parsed['subject']['CN']);
        }

        return [
            'expires_at' => Carbon::createFromTimestamp($parsed['validTo_time_t']),
            'domains' => array_values(array_unique($domains)),
        ];
    }
}
