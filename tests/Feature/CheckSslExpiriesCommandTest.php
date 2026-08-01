<?php

use App\Actions\SSL\CertificateParser;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Facades\Notifier;
use App\Facades\SSH;
use App\Jobs\SSL\CheckSslExpiryJob;
use App\Models\Ssl;
use App\Notifications\SslCertificateExpiring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

function vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl(array $attributes = []): Ssl
{
    return Ssl::factory()->create(array_merge([
        'site_id' => test()->site->id,
        'type' => SslType::LETSENCRYPT,
        'status' => SslStatus::CREATED,
        'certificate_path' => '/etc/letsencrypt/live/1/fullchain.pem',
        'pk_path' => '/etc/letsencrypt/live/1/privkey.pem',
        'expires_at' => now()->addDays(60),
        'domains' => ['example.com'],
    ], $attributes));
}

function vitoPestFeatureCheckSslExpiriesCommandTestGenerateTestCertificate(int $validDays = 90): string
{
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $csr = openssl_csr_new([
        'CN' => 'example.com',
    ], $key, ['config' => vitoPestFeatureCheckSslExpiriesCommandTestOpensslConfig()]);

    $cert = openssl_csr_sign($csr, null, $key, $validDays, [
        'config' => vitoPestFeatureCheckSslExpiriesCommandTestOpensslConfig(),
        'x509_extensions' => 'v3_req',
    ]);

    openssl_x509_export($cert, $certPem);

    return $certPem;
}

function vitoPestFeatureCheckSslExpiriesCommandTestOpensslConfig(): string
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

test('dispatches job for site level le ssl', function () {
    Bus::fake();

    vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl();

    $this->artisan('ssl:check-expiry')->assertSuccessful();

    Bus::assertDispatched(CheckSslExpiryJob::class);
});

test('does not dispatch for server level ssl', function () {
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
});

test('does not dispatch for custom ssl', function () {
    Bus::fake();

    vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl(['type' => SslType::CUSTOM]);

    $this->artisan('ssl:check-expiry')->assertSuccessful();

    Bus::assertNotDispatched(CheckSslExpiryJob::class);
});

test('does not dispatch for failed ssl', function () {
    Bus::fake();

    vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl(['status' => SslStatus::FAILED]);

    $this->artisan('ssl:check-expiry')->assertSuccessful();

    Bus::assertNotDispatched(CheckSslExpiryJob::class);
});

test('does not dispatch for ssl without certificate path', function () {
    Bus::fake();

    vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl(['certificate_path' => null]);

    $this->artisan('ssl:check-expiry')->assertSuccessful();

    Bus::assertNotDispatched(CheckSslExpiryJob::class);
});

test('dispatches one job per server', function () {
    Bus::fake();

    vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl();
    vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl([
        'certificate_path' => '/etc/letsencrypt/live/2/fullchain.pem',
    ]);

    $this->artisan('ssl:check-expiry')->assertSuccessful();

    Bus::assertDispatchedTimes(CheckSslExpiryJob::class, 1);
});

test('job updates expires at when cert differs', function () {
    $certPem = vitoPestFeatureCheckSslExpiriesCommandTestGenerateTestCertificate(90);
    SSH::fake($certPem);

    $ssl = vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl([
        'expires_at' => now()->addDays(10),
    ]);

    $oldExpiry = $ssl->expires_at->copy();

    $job = new CheckSslExpiryJob($this->server);
    $job->handle();

    $ssl->refresh();
    expect($ssl->expires_at->equalTo($oldExpiry))->toBeFalse();
});

test('job does not update when cert matches', function () {
    $certPem = vitoPestFeatureCheckSslExpiriesCommandTestGenerateTestCertificate(60);
    $parsed = CertificateParser::parse($certPem);

    SSH::fake($certPem);

    $ssl = vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl([
        'expires_at' => $parsed['expires_at'],
        'domains' => $parsed['domains'],
    ]);

    $job = new CheckSslExpiryJob($this->server);
    $job->handle();

    $ssl->refresh();
    expect($ssl->expires_at->equalTo($parsed['expires_at']))->toBeTrue();
});

test('job skips unreadable cert without error', function () {
    SSH::fake('');

    $ssl = vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl([
        'expires_at' => now()->addDays(10),
    ]);

    $originalExpiry = $ssl->expires_at->toDateTimeString();

    $job = new CheckSslExpiryJob($this->server);
    $job->handle();

    $ssl->refresh();
    expect($ssl->expires_at->toDateTimeString())->toEqual($originalExpiry);
});

test('job updates domains from cert', function () {
    $certPem = vitoPestFeatureCheckSslExpiriesCommandTestGenerateTestCertificate(90);
    SSH::fake($certPem);

    $ssl = vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl([
        'expires_at' => now()->addDays(10),
        'domains' => ['old.example.com'],
    ]);

    $job = new CheckSslExpiryJob($this->server);
    $job->handle();

    $ssl->refresh();
    expect($ssl->domains)->toContain('example.com');
});

test('notifies once when certificate expiring soon', function () {
    SSH::fake(vitoPestFeatureCheckSslExpiriesCommandTestGenerateTestCertificate(10));
    Notifier::spy();

    $ssl = vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl();

    $job = new CheckSslExpiryJob($this->server);
    $job->handle();
    $job->handle();

    $ssl->refresh();
    expect($ssl->expiry_notified_at)->not->toBeNull();
    Notifier::shouldHaveReceived('send')->withArgs(
        fn (object $notifiable, object $notification): bool => $notification instanceof SslCertificateExpiring
    )->once();
});

test('does not notify when not expiring soon', function () {
    SSH::fake(vitoPestFeatureCheckSslExpiriesCommandTestGenerateTestCertificate(90));
    Notifier::spy();

    $ssl = vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl();

    (new CheckSslExpiryJob($this->server))->handle();

    $ssl->refresh();
    expect($ssl->expiry_notified_at)->toBeNull();
    Notifier::shouldNotHaveReceived('send');
});

test('rearms notification after renewal', function () {
    Notifier::spy();
    $ssl = vitoPestFeatureCheckSslExpiriesCommandTestCreateSiteLeSsl();

    SSH::fake(vitoPestFeatureCheckSslExpiriesCommandTestGenerateTestCertificate(10));
    (new CheckSslExpiryJob($this->server))->handle();
    $ssl->refresh();
    expect($ssl->expiry_notified_at)->not->toBeNull();

    SSH::fake(vitoPestFeatureCheckSslExpiriesCommandTestGenerateTestCertificate(90));
    (new CheckSslExpiryJob($this->server))->handle();
    $ssl->refresh();
    expect($ssl->expiry_notified_at)->toBeNull();
});
