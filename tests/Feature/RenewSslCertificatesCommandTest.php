<?php

namespace Tests\Feature;

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\CreateLetsEncryptWildcardSslJob;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RenewSslCertificatesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_renewal_for_expiring_wildcard_ssl(): void
    {
        Bus::fake();

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => SslType::LETSENCRYPT,
            'is_wildcard' => true,
            'status' => SslStatus::CREATED,
            'expires_at' => now()->addDays(15),
            'domains' => ['*.example.com', 'example.com'],
        ]);

        $this->artisan('ssl:renew-wildcards')->assertSuccessful();

        Bus::assertDispatched(CreateLetsEncryptWildcardSslJob::class);
    }

    public function test_does_not_dispatch_for_ssl_not_expiring_soon(): void
    {
        Bus::fake();

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => SslType::LETSENCRYPT,
            'is_wildcard' => true,
            'status' => SslStatus::CREATED,
            'expires_at' => now()->addDays(60),
            'domains' => ['*.example.com'],
        ]);

        $this->artisan('ssl:renew-wildcards')->assertSuccessful();

        Bus::assertNotDispatched(CreateLetsEncryptWildcardSslJob::class);
    }

    public function test_does_not_dispatch_for_non_wildcard_ssl(): void
    {
        Bus::fake();

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => SslType::LETSENCRYPT,
            'is_wildcard' => false,
            'status' => SslStatus::CREATED,
            'expires_at' => now()->addDays(15),
            'domains' => ['example.com'],
        ]);

        $this->artisan('ssl:renew-wildcards')->assertSuccessful();

        Bus::assertNotDispatched(CreateLetsEncryptWildcardSslJob::class);
    }

    public function test_does_not_dispatch_for_creating_ssl(): void
    {
        Bus::fake();

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => SslType::LETSENCRYPT,
            'is_wildcard' => true,
            'status' => SslStatus::CREATING,
            'expires_at' => now()->addDays(15),
            'domains' => ['*.example.com'],
        ]);

        $this->artisan('ssl:renew-wildcards')->assertSuccessful();

        Bus::assertNotDispatched(CreateLetsEncryptWildcardSslJob::class);
    }

    public function test_does_not_dispatch_for_failed_ssl(): void
    {
        Bus::fake();

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => SslType::LETSENCRYPT,
            'is_wildcard' => true,
            'status' => SslStatus::FAILED,
            'expires_at' => now()->addDays(15),
            'domains' => ['*.example.com'],
        ]);

        $this->artisan('ssl:renew-wildcards')->assertSuccessful();

        Bus::assertNotDispatched(CreateLetsEncryptWildcardSslJob::class);
    }

    public function test_does_not_dispatch_for_site_level_ssl(): void
    {
        Bus::fake();

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'type' => SslType::LETSENCRYPT,
            'is_wildcard' => true,
            'status' => SslStatus::CREATED,
            'expires_at' => now()->addDays(15),
            'domains' => ['*.example.com'],
        ]);

        $this->artisan('ssl:renew-wildcards')->assertSuccessful();

        Bus::assertNotDispatched(CreateLetsEncryptWildcardSslJob::class);
    }

    public function test_does_not_dispatch_for_custom_ssl_type(): void
    {
        Bus::fake();

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => SslType::CUSTOM,
            'is_wildcard' => true,
            'status' => SslStatus::CREATED,
            'expires_at' => now()->addDays(15),
            'domains' => ['*.example.com'],
        ]);

        $this->artisan('ssl:renew-wildcards')->assertSuccessful();

        Bus::assertNotDispatched(CreateLetsEncryptWildcardSslJob::class);
    }

    public function test_dispatches_for_ssl_at_exactly_30_days(): void
    {
        Bus::fake();

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => SslType::LETSENCRYPT,
            'is_wildcard' => true,
            'status' => SslStatus::CREATED,
            'expires_at' => now()->addDays(30),
            'domains' => ['*.example.com'],
        ]);

        $this->artisan('ssl:renew-wildcards')->assertSuccessful();

        Bus::assertDispatched(CreateLetsEncryptWildcardSslJob::class);
    }

    public function test_dispatches_multiple_expiring_ssls(): void
    {
        Bus::fake();

        Ssl::factory()->count(3)->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'type' => SslType::LETSENCRYPT,
            'is_wildcard' => true,
            'status' => SslStatus::CREATED,
            'expires_at' => now()->addDays(10),
            'domains' => ['*.example.com'],
        ]);

        $this->artisan('ssl:renew-wildcards')->assertSuccessful();

        Bus::assertDispatchedTimes(CreateLetsEncryptWildcardSslJob::class, 3);
    }
}
