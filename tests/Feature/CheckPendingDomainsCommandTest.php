<?php

namespace Tests\Feature;

use App\Enums\HostedDomainStatus;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CheckPendingDomainsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_job_for_pending_domains(): void
    {
        Bus::fake();

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'pending.example.com',
            'status' => HostedDomainStatus::PENDING,
            'updated_at' => now()->subHours(1),
        ]);

        $this->artisan('domains:check-pending')->assertSuccessful();

        Bus::assertDispatched(CheckDomainJob::class);
    }

    public function test_does_not_dispatch_for_active_domains(): void
    {
        Bus::fake();

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'active.example.com',
            'status' => HostedDomainStatus::ACTIVE,
            'updated_at' => now()->subHours(1),
        ]);

        $this->artisan('domains:check-pending')->assertSuccessful();

        Bus::assertNotDispatched(CheckDomainJob::class);
    }

    public function test_does_not_dispatch_for_old_pending_domains(): void
    {
        Bus::fake();

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'old-pending.example.com',
            'status' => HostedDomainStatus::PENDING,
            'updated_at' => now()->subHours(25),
        ]);

        $this->artisan('domains:check-pending')->assertSuccessful();

        Bus::assertNotDispatched(CheckDomainJob::class);
    }

    public function test_dispatches_for_multiple_pending_domains(): void
    {
        Bus::fake();

        HostedDomain::factory()->count(3)->create([
            'site_id' => $this->site->id,
            'status' => HostedDomainStatus::PENDING,
            'updated_at' => now()->subHours(1),
        ]);

        $this->artisan('domains:check-pending')->assertSuccessful();

        Bus::assertDispatchedTimes(CheckDomainJob::class, 3);
    }
}
