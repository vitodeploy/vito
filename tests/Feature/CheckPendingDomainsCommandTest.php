<?php

use App\Enums\HostedDomainStatus;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('dispatches job for pending domains', function () {
    Bus::fake();

    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'pending.example.com',
        'status' => HostedDomainStatus::PENDING,
        'updated_at' => now()->subHours(1),
    ]);

    $this->artisan('domains:check-pending')->assertSuccessful();

    Bus::assertDispatched(CheckDomainJob::class);
});

test('does not dispatch for active domains', function () {
    Bus::fake();

    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'active.example.com',
        'status' => HostedDomainStatus::ACTIVE,
        'updated_at' => now()->subHours(1),
    ]);

    $this->artisan('domains:check-pending')->assertSuccessful();

    Bus::assertNotDispatched(CheckDomainJob::class);
});

test('does not dispatch for old pending domains', function () {
    Bus::fake();

    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'old-pending.example.com',
        'status' => HostedDomainStatus::PENDING,
        'updated_at' => now()->subHours(25),
    ]);

    $this->artisan('domains:check-pending')->assertSuccessful();

    Bus::assertNotDispatched(CheckDomainJob::class);
});

test('dispatches for multiple pending domains', function () {
    Bus::fake();

    HostedDomain::factory()->count(3)->create([
        'site_id' => $this->site->id,
        'status' => HostedDomainStatus::PENDING,
        'updated_at' => now()->subHours(1),
    ]);

    $this->artisan('domains:check-pending')->assertSuccessful();

    Bus::assertDispatchedTimes(CheckDomainJob::class, 3);
});
