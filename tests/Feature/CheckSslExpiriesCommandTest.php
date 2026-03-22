<?php

namespace Tests\Feature;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Facades\SSH;
use App\Jobs\SSL\CheckSslExpiryJob;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CheckSslExpiriesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createSiteLeSsl(array $attributes = []): Ssl
    {
        return Ssl::factory()->create(array_merge([
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATED,
            'certificate_path' => '/etc/letsencrypt/live/1/fullchain.pem',
            'pk_path' => '/etc/letsencrypt/live/1/privkey.pem',
            'expires_at' => now()->addDays(60),
            'domains' => ['example.com'],
        ], $attributes));
    }

    private function generateTestCertificate(int $validDays = 90): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new([
            'CN' => 'example.com',
        ], $key, ['config' => $this->opensslConfig()]);

        $cert = openssl_csr_sign($csr, null, $key, $validDays, [
            'config' => $this->opensslConfig(),
            'x509_extensions' => 'v3_req',
        ]);

        openssl_x509_export($cert, $certPem);

        return $certPem;
    }

    private function opensslConfig(): string
    {
        $configPath = tempnam(sys_get_temp_dir(), 'openssl');
        file_put_contents($configPath, <<<'INI'
[req]
distinguished_name = req_dn
req_extensions = v3_req

[req_dn]
CN = example.com

[v3_req]
subjectAltName = DNS:example.com,DNS:www.example.com
INI);

        return $configPath;
    }

    public function test_dispatches_job_for_site_level_le_ssl(): void
    {
        Bus::fake();

        $this->createSiteLeSsl();

        $this->artisan('ssl:check-expiry')->assertSuccessful();

        Bus::assertDispatched(CheckSslExpiryJob::class);
    }

    public function test_does_not_dispatch_for_server_level_ssl(): void
    {
        Bus::fake();

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => SslType::LETSENCRYPT,
            'status' => SslStatus::CREATED,
            'certificate_path' => '/etc/letsencrypt/live/1/fullchain.pem',
            'expires_at' => now()->addDays(60),
        ]);

        $this->artisan('ssl:check-expiry')->assertSuccessful();

        Bus::assertNotDispatched(CheckSslExpiryJob::class);
    }

    public function test_does_not_dispatch_for_custom_ssl(): void
    {
        Bus::fake();

        $this->createSiteLeSsl(['type' => SslType::CUSTOM]);

        $this->artisan('ssl:check-expiry')->assertSuccessful();

        Bus::assertNotDispatched(CheckSslExpiryJob::class);
    }

    public function test_does_not_dispatch_for_failed_ssl(): void
    {
        Bus::fake();

        $this->createSiteLeSsl(['status' => SslStatus::FAILED]);

        $this->artisan('ssl:check-expiry')->assertSuccessful();

        Bus::assertNotDispatched(CheckSslExpiryJob::class);
    }

    public function test_does_not_dispatch_for_ssl_without_certificate_path(): void
    {
        Bus::fake();

        $this->createSiteLeSsl(['certificate_path' => null]);

        $this->artisan('ssl:check-expiry')->assertSuccessful();

        Bus::assertNotDispatched(CheckSslExpiryJob::class);
    }

    public function test_dispatches_one_job_per_server(): void
    {
        Bus::fake();

        $this->createSiteLeSsl();
        $this->createSiteLeSsl([
            'certificate_path' => '/etc/letsencrypt/live/2/fullchain.pem',
        ]);

        $this->artisan('ssl:check-expiry')->assertSuccessful();

        Bus::assertDispatchedTimes(CheckSslExpiryJob::class, 1);
    }

    public function test_job_updates_expires_at_when_cert_differs(): void
    {
        $certPem = $this->generateTestCertificate(90);
        SSH::fake($certPem);

        $ssl = $this->createSiteLeSsl([
            'expires_at' => now()->addDays(10),
        ]);

        $oldExpiry = $ssl->expires_at->copy();

        $job = new CheckSslExpiryJob($this->server);
        $job->handle();

        $ssl->refresh();
        $this->assertFalse($ssl->expires_at->equalTo($oldExpiry));
    }

    public function test_job_does_not_update_when_cert_matches(): void
    {
        $certPem = $this->generateTestCertificate(60);
        $parsed = \App\Actions\SSL\CertificateParser::parse($certPem);

        SSH::fake($certPem);

        $ssl = $this->createSiteLeSsl([
            'expires_at' => $parsed['expires_at'],
            'domains' => $parsed['domains'],
        ]);

        $job = new CheckSslExpiryJob($this->server);
        $job->handle();

        $ssl->refresh();
        $this->assertTrue($ssl->expires_at->equalTo($parsed['expires_at']));
    }

    public function test_job_skips_unreadable_cert_without_error(): void
    {
        SSH::fake('');

        $ssl = $this->createSiteLeSsl([
            'expires_at' => now()->addDays(10),
        ]);

        $originalExpiry = $ssl->expires_at->toDateTimeString();

        $job = new CheckSslExpiryJob($this->server);
        $job->handle();

        $ssl->refresh();
        $this->assertEquals($originalExpiry, $ssl->expires_at->toDateTimeString());
    }

    public function test_job_updates_domains_from_cert(): void
    {
        $certPem = $this->generateTestCertificate(90);
        SSH::fake($certPem);

        $ssl = $this->createSiteLeSsl([
            'expires_at' => now()->addDays(10),
            'domains' => ['old.example.com'],
        ]);

        $job = new CheckSslExpiryJob($this->server);
        $job->handle();

        $ssl->refresh();
        $this->assertContains('example.com', $ssl->domains);
    }
}
