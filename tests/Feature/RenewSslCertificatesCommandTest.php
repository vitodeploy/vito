<?php

use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Jobs\SSL\CreateLetsEncryptWildcardSslJob;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('dispatches renewal for expiring wildcard ssl', function () {
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
});

test('does not dispatch renewal', function (array $attributes) {
    Bus::fake();

    Ssl::factory()->create(array_merge([
        'server_id' => $this->server->id,
        'site_id' => null,
        'type' => SslType::LETSENCRYPT,
        'is_wildcard' => true,
        'status' => SslStatus::CREATED,
        'expires_at' => now()->addDays(15),
        'domains' => ['*.example.com'],
    ], $attributes));

    $this->artisan('ssl:renew-wildcards')->assertSuccessful();

    Bus::assertNotDispatched(CreateLetsEncryptWildcardSslJob::class);
})->with('nonRenewableSsl');

test('does not dispatch for site level ssl', function () {
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
});

test('dispatches for ssl at exactly 30 days', function () {
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
});

dataset('nonRenewableSsl', /** @return array<string, array{0: array<string, mixed>}> */ function (): array {
    return [
        'not expiring soon' => [['expires_at' => now()->addDays(60)]],
        'not wildcard' => [['is_wildcard' => false, 'domains' => ['example.com']]],
        'creating' => [['status' => SslStatus::CREATING]],
        'failed' => [['status' => SslStatus::FAILED]],
        'custom type' => [['type' => SslType::CUSTOM]],
    ];
});

test('dispatches multiple expiring ssls', function () {
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
});
