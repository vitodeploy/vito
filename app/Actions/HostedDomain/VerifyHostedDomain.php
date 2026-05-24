<?php

namespace App\Actions\HostedDomain;

use App\Actions\Site\EnsureSiteVerificationKey;
use App\DTOs\VerificationResult;
use App\Models\HostedDomain;
use App\Models\Site;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class VerifyHostedDomain
{
    private const DOH_PROVIDERS = [
        'https://cloudflare-dns.com/dns-query',
        'https://dns.google/resolve',
    ];

    public function __construct(
        private readonly EnsureSiteVerificationKey $ensureKey,
    ) {}

    public function verify(HostedDomain $hostedDomain): VerificationResult
    {
        $site = $hostedDomain->site;

        if (! $site instanceof Site) {
            return VerificationResult::failure('Site not found for hosted domain.');
        }

        $key = $this->ensureKey->ensure($site);
        $challengeId = bin2hex(random_bytes(16));
        $expected = $this->expectedBody($site, $hostedDomain, $challengeId);

        $remoteDir = "/var/lib/vito/verify/{$key}";
        $remoteFile = "{$remoteDir}/{$challengeId}";

        try {
            $site->server->ssh()
                ->asUser('root')
                ->exec(
                    view('ssh.verification.write-challenge', [
                        'dir' => $remoteDir,
                        'file' => $remoteFile,
                        'body' => base64_encode($expected),
                    ]),
                    'write-verification-challenge',
                    $site->id,
                );
        } catch (Throwable $e) {
            Log::warning('hosted-domain verification: failed to stage challenge', [
                'domain' => $hostedDomain->domain,
                'site_id' => $site->id,
            ]);

            return VerificationResult::failure('Could not stage verification challenge on server.');
        }

        try {
            $ips = $this->resolveIps($hostedDomain->domain);

            if ($ips === []) {
                return VerificationResult::failure('Domain could not be reached via HTTP.');
            }

            $matched = false;
            $perIpStatus = [];

            foreach ($ips as $ip) {
                $status = $this->fetchAndCompare($hostedDomain->domain, $ip, $key, $challengeId, $expected);
                $perIpStatus[$ip] = $status['code'];

                if ($status['matched']) {
                    $matched = true;
                    break;
                }
            }

            Log::info('hosted-domain verification: result', [
                'domain' => $hostedDomain->domain,
                'verified' => $matched,
                'per_ip_status' => $perIpStatus,
            ]);

            return $matched
                ? VerificationResult::success()
                : VerificationResult::failure('Domain did not respond with the expected challenge from any resolved IP.');
        } finally {
            $this->cleanupChallenge($site, $remoteFile);
        }
    }

    private function expectedBody(Site $site, HostedDomain $hostedDomain, string $challengeId): string
    {
        return hash_hmac('sha256', implode("\n", [
            (string) $site->server_id,
            (string) $site->id,
            $hostedDomain->domain,
            $challengeId,
        ]), (string) config('app.key'));
    }

    /**
     * @return array<int, string>
     */
    private function resolveIps(string $domain): array
    {
        $ips = [];

        foreach (self::DOH_PROVIDERS as $endpoint) {
            foreach (['A', 'AAAA'] as $type) {
                try {
                    $response = Http::timeout(5)
                        ->retry(2, 100, throw: false)
                        ->withHeaders(['Accept' => 'application/dns-json'])
                        ->get($endpoint, ['name' => $domain, 'type' => $type]);

                    if (! $response->successful()) {
                        continue;
                    }

                    foreach ($response->json('Answer', []) as $answer) {
                        $data = $answer['data'] ?? null;
                        if (is_string($data) && filter_var($data, FILTER_VALIDATE_IP)) {
                            $ips[] = $data;
                        }
                    }
                } catch (Throwable) {
                    continue;
                }
            }

            if ($ips !== []) {
                return array_values(array_unique($ips));
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * @return array{matched: bool, code: int}
     */
    private function fetchAndCompare(string $domain, string $ip, string $key, string $challengeId, string $expected): array
    {
        $url = sprintf('http://%s/.well-known/vito/%s/%s?_=%s', $domain, $key, $challengeId, Str::random(8));
        $version = (string) config('app.version', '4.x');

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Cache-Control' => 'no-cache',
                    'Pragma' => 'no-cache',
                    'User-Agent' => sprintf('Vito/%s (domain-verification)', $version),
                ])
                ->withOptions([
                    'allow_redirects' => false,
                    'curl' => [
                        CURLOPT_RESOLVE => [sprintf('%s:80:%s', $domain, $ip)],
                    ],
                ])
                ->get($url);

            $matched = $response->successful()
                && hash_equals($expected, trim($response->body()));

            return ['matched' => $matched, 'code' => $response->status()];
        } catch (Throwable) {
            return ['matched' => false, 'code' => 0];
        }
    }

    private function cleanupChallenge(Site $site, string $remoteFile): void
    {
        try {
            $site->server->ssh()
                ->asUser('root')
                ->exec('rm -f '.escapeshellarg($remoteFile), 'cleanup-verification-challenge', $site->id);
        } catch (Throwable) {
            Log::warning('hosted-domain verification: cleanup failed', [
                'site_id' => $site->id,
            ]);
        }
    }
}
