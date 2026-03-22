<?php

namespace App\Jobs\SSL;

use App\Actions\Domain\CreateDNSRecord;
use App\Actions\Domain\DeleteDNSRecord;
use App\Actions\SSL\CertificateParser;
use App\DTOs\SocketEventDTO;
use App\Enums\SslStatus;
use App\Events\SocketEvent;
use App\Helpers\SSH;
use App\Http\Resources\SslResource;
use App\Models\DNSRecord;
use App\Models\Domain;
use App\Models\Server;
use App\Models\ServerLog;
use App\Models\Ssl;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateLetsEncryptWildcardSslJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 600;

    public function __construct(protected Server $server, protected Ssl $ssl) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function (): void {
            $domain = $this->ssl->domain;

            if (! $domain) {
                throw new Exception('Domain not found for SSL #'.$this->ssl->id.'. It may have been deleted.');
            }

            $basePath = '/etc/ssl/vito/'.$this->ssl->id;
            $certName = (string) $this->ssl->id;
            $ssh = $this->server->ssh()->setLog($this->ssl->log);

            Log::info('[Wildcard SSL] Starting job', [
                'ssl_id' => $this->ssl->id,
                'domain' => $domain->domain,
            ]);

            $this->prepareWorkspace($ssh, $basePath, $domain);
            $pid = $this->startCertbot($ssh, $basePath, $certName, $domain->domain);
            $this->processChallenges($ssh, $basePath, $domain, $pid);
            $this->readAndStoreCertificate($ssh, $certName);

            // Cleanup signal files and hook scripts
            $ssh->exec(view('ssh.ssl.wildcard-cleanup-artifacts', ['basePath' => $basePath]));

            Log::info('[Wildcard SSL] Job completed successfully', ['ssl_id' => $this->ssl->id]);
            $this->broadcastSslUpdate();
        });
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[Wildcard SSL] Job failed', [
            'ssl_id' => $this->ssl->id,
            'error' => $e->getMessage(),
        ]);

        // Clean up any DNS records that were created
        $dnsRecordIds = ($this->ssl->csr_data ?? [])['dns_record_ids'] ?? [];
        foreach ($dnsRecordIds as $recordId) {
            $this->deleteDnsRecordSafely($recordId);
        }

        $this->ssl->status = SslStatus::FAILED;
        $this->ssl->save();
        $this->broadcastSslUpdate();

        ServerLog::log(
            $this->server,
            'create-wildcard-ssl-failed',
            $e->getMessage(),
        );
    }

    private function prepareWorkspace(SSH $ssh, string $basePath, Domain $domain): void
    {
        $ssh->exec(view('ssh.ssl.wildcard-prepare', ['basePath' => $basePath]));
        $this->cleanupOrphanedDnsRecords($domain);

        $authHook = view('ssh.ssl.wildcard-auth-hook', ['basePath' => $basePath])->render();
        $ssh->write("{$basePath}/auth-hook.sh", $authHook, 'root');

        $cleanupHook = view('ssh.ssl.wildcard-cleanup-hook', ['basePath' => $basePath])->render();
        $ssh->write("{$basePath}/cleanup-hook.sh", $cleanupHook, 'root');

        $ssh->exec("sudo chmod 700 {$basePath}/auth-hook.sh {$basePath}/cleanup-hook.sh");
    }

    private function startCertbot(SSH $ssh, string $basePath, string $certName, string $domain): int
    {
        $certbotCommand = view('ssh.ssl.create-wildcard-ssl', [
            'email' => $this->ssl->email,
            'certName' => $certName,
            'domain' => $domain,
            'basePath' => $basePath,
        ])->render();

        $pidOutput = trim($ssh->exec($certbotCommand));
        $pid = (int) trim(collect(explode("\n", $pidOutput))->last());

        Log::info('[Wildcard SSL] Certbot started', ['pid' => $pid]);

        if ($pid <= 0) {
            throw new Exception('Failed to start certbot process');
        }

        $ssh->exec("echo {$pid} | sudo tee {$basePath}/certbot.pid > /dev/null");

        return $pid;
    }

    private function processChallenges(SSH $ssh, string $basePath, Domain $domain, int $pid): void
    {
        $authProcessed = 0;
        $cleanupProcessed = 0;
        $dnsRecordIds = ($this->ssl->csr_data ?? [])['dns_record_ids'] ?? [];
        $maxWait = 600;
        $elapsed = 0;

        while ($elapsed < $maxWait) {
            sleep(3);
            $elapsed += 3;

            // Check for new auth signals
            $nextAuth = $authProcessed + 1;
            $signalContent = trim($ssh->exec("cat {$basePath}/auth-signal-{$nextAuth} 2>/dev/null || true"));

            if ($signalContent !== '') {
                $parts = explode('|', $signalContent, 2);
                if (count($parts) === 2) {
                    $validation = $parts[1];

                    Log::info('[Wildcard SSL] Auth signal received, creating TXT record', [
                        'signal' => $nextAuth,
                        'validation' => $validation,
                    ]);

                    $dnsRecord = app(CreateDNSRecord::class)->create($domain, [
                        'type' => 'TXT',
                        'name' => '_acme-challenge.'.$domain->domain,
                        'content' => $validation,
                        'ttl' => 120,
                    ]);

                    $dnsRecordIds[] = $dnsRecord->id;
                    $this->updateDnsRecordIds($dnsRecordIds);

                    $this->waitForDnsPropagation($domain->domain, $validation);

                    $ssh->exec("echo 'proceed' | sudo tee {$basePath}/auth-proceed-{$nextAuth} > /dev/null");
                    $authProcessed = $nextAuth;
                }
            }

            // Check for cleanup signals
            $nextCleanup = $cleanupProcessed + 1;
            $cleanupContent = trim($ssh->exec("cat {$basePath}/cleanup-signal-{$nextCleanup} 2>/dev/null || true"));

            if ($cleanupContent !== '') {
                if (isset($dnsRecordIds[$cleanupProcessed])) {
                    $this->deleteDnsRecordSafely($dnsRecordIds[$cleanupProcessed]);
                }

                $ssh->exec("echo 'done' | sudo tee {$basePath}/cleanup-done-{$nextCleanup} > /dev/null");
                $cleanupProcessed = $nextCleanup;
            }

            // Check if certbot is still running
            $processCheck = trim($ssh->exec("sudo kill -0 {$pid} 2>/dev/null && echo 'RUNNING' || echo 'STOPPED'"));
            if ($processCheck === 'STOPPED') {
                Log::info('[Wildcard SSL] Certbot process stopped', ['elapsed' => $elapsed]);
                break;
            }
        }

        if ($elapsed >= $maxWait) {
            $ssh->exec("sudo kill {$pid} 2>/dev/null || true");
            throw new Exception('Certbot timed out after '.$maxWait.' seconds');
        }

        // Verify certbot succeeded
        $certbotLog = $ssh->exec("cat {$basePath}/certbot.log 2>/dev/null || true");

        if (! str_contains($certbotLog, 'Successfully received certificate')) {
            throw new Exception('Certbot failed: '.$certbotLog);
        }
    }

    private function readAndStoreCertificate(SSH $ssh, string $certName): void
    {
        $certPath = "/etc/letsencrypt/live/{$certName}/fullchain.pem";
        $pkPath = "/etc/letsencrypt/live/{$certName}/privkey.pem";

        $certificate = trim($ssh->exec("sudo cat {$certPath}"));

        if (empty($certificate) || ! str_contains($certificate, 'BEGIN CERTIFICATE')) {
            throw new Exception('Failed to read certificate from server');
        }

        $parsed = CertificateParser::parse($certificate);

        Log::info('[Wildcard SSL] Certificate parsed', [
            'domains' => $parsed['domains'],
            'expires_at' => $parsed['expires_at']->toIso8601String(),
        ]);

        $this->ssl->certificate_path = $certPath;
        $this->ssl->pk_path = $pkPath;
        $this->ssl->expires_at = $parsed['expires_at'];
        $this->ssl->domains = $parsed['domains'];
        $this->ssl->status = SslStatus::CREATED;
        $this->ssl->save();
    }

    /**
     * @param  array<int>  $dnsRecordIds
     */
    private function updateDnsRecordIds(array $dnsRecordIds): void
    {
        $csrData = $this->ssl->csr_data ?? [];
        $csrData['dns_record_ids'] = $dnsRecordIds;
        $this->ssl->csr_data = $csrData;
        $this->ssl->save();
    }

    /**
     * Safely delete a DNS record by ID, logging any failures.
     */
    private function deleteDnsRecordSafely(int $recordId): void
    {
        $record = DNSRecord::query()->find($recordId);
        if (! $record) {
            return;
        }

        try {
            app(DeleteDNSRecord::class)->delete($record);
        } catch (\Throwable $e) {
            Log::warning('[Wildcard SSL] Failed to delete DNS record', [
                'record_id' => $recordId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete any existing _acme-challenge TXT records for this domain
     * left over from previous failed attempts.
     */
    private function cleanupOrphanedDnsRecords(Domain $domain): void
    {
        $orphanedRecords = DNSRecord::query()
            ->where('domain_id', $domain->id)
            ->where('type', 'TXT')
            ->where('name', '_acme-challenge.'.$domain->domain)
            ->get();

        foreach ($orphanedRecords as $record) {
            try {
                app(DeleteDNSRecord::class)->delete($record);
            } catch (\Throwable) {
                // Best effort
            }
        }
    }

    private function broadcastSslUpdate(): void
    {
        $this->ssl->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: 'ssl.updated',
            data: new SslResource($this->ssl),
        ));
    }

    private function waitForDnsPropagation(string $domain, string $validation): void
    {
        $ssh = $this->server->ssh();
        $escapedDomain = escapeshellarg('_acme-challenge.'.$domain);
        $maxWait = 60;
        $elapsed = 0;

        while ($elapsed < $maxWait) {
            $result = trim($ssh->exec("dig +short TXT {$escapedDomain} @1.1.1.1 2>/dev/null || true"));
            if (str_contains($result, $validation)) {
                return;
            }
            sleep(5);
            $elapsed += 5;
        }

        Log::warning('[Wildcard SSL] DNS propagation timed out, proceeding anyway', [
            'domain' => $domain,
        ]);
    }
}
