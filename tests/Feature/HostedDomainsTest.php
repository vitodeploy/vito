<?php

namespace Tests\Feature;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Facades\SSH;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HostedDomainsTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_hosted_domains_list(): void
    {
        $this->actingAs($this->user);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'alias.example.com',
        ]);

        $this->get(route('hosted-domains', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('hosted-domains/index')
                ->has('hostedDomains.data', 1)
            );
    }

    public function test_create_alias_domain(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('hosted-domains.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'domain' => 'alias.example.com',
            'type' => HostedDomainType::ALIAS->value,
            'ssl_method' => SslMethod::NONE->value,
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('hosted_domains', [
            'site_id' => $this->site->id,
            'domain' => 'alias.example.com',
            'type' => HostedDomainType::ALIAS->value,
            'ssl_method' => SslMethod::NONE->value,
        ]);
    }

    public function test_create_redirect_domain(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('hosted-domains.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'domain' => 'redirect.example.com',
            'type' => HostedDomainType::REDIRECT->value,
            'ssl_method' => SslMethod::NONE->value,
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('hosted_domains', [
            'site_id' => $this->site->id,
            'domain' => 'redirect.example.com',
            'type' => HostedDomainType::REDIRECT->value,
        ]);
    }

    public function test_create_domain_validation_requires_fields(): void
    {
        $this->actingAs($this->user);

        $this->post(route('hosted-domains.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [])
            ->assertSessionHasErrors(['domain', 'type', 'ssl_method']);
    }

    public function test_create_domain_validation_invalid_domain_format(): void
    {
        $this->actingAs($this->user);

        $this->post(route('hosted-domains.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'domain' => 'not a domain',
            'type' => HostedDomainType::ALIAS->value,
            'ssl_method' => SslMethod::NONE->value,
        ])
            ->assertSessionHasErrors('domain');
    }

    public function test_create_domain_rejects_primary_type(): void
    {
        $this->actingAs($this->user);

        $this->post(route('hosted-domains.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'domain' => 'primary.example.com',
            'type' => HostedDomainType::PRIMARY->value,
            'ssl_method' => SslMethod::NONE->value,
        ])
            ->assertSessionHasErrors('type');
    }

    public function test_create_domain_rejects_duplicate_on_same_server(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'duplicate.example.com',
        ]);

        $this->post(route('hosted-domains.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'domain' => 'duplicate.example.com',
            'type' => HostedDomainType::ALIAS->value,
            'ssl_method' => SslMethod::NONE->value,
        ])
            ->assertSessionHasErrors('domain');
    }

    public function test_create_domain_with_custom_ssl(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $ssl = Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'domains' => ['*.example.com'],
        ]);

        $this->post(route('hosted-domains.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'domain' => 'app.example.com',
            'type' => HostedDomainType::ALIAS->value,
            'ssl_method' => SslMethod::CUSTOM->value,
            'ssl_id' => $ssl->id,
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('hosted_domains', [
            'site_id' => $this->site->id,
            'domain' => 'app.example.com',
            'ssl_method' => SslMethod::CUSTOM->value,
            'ssl_id' => $ssl->id,
        ]);
    }

    public function test_create_domain_custom_ssl_requires_ssl_id(): void
    {
        $this->actingAs($this->user);

        $this->post(route('hosted-domains.store', [
            'server' => $this->server->id,
            'site' => $this->site->id,
        ]), [
            'domain' => 'app.example.com',
            'type' => HostedDomainType::ALIAS->value,
            'ssl_method' => SslMethod::CUSTOM->value,
        ])
            ->assertSessionHasErrors('ssl_id');
    }

    public function test_update_domain(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'old.example.com',
            'type' => HostedDomainType::ALIAS,
            'status' => HostedDomainStatus::ACTIVE,
            'ssl_method' => SslMethod::NONE,
        ]);

        $this->put(route('hosted-domains.update', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]), [
            'domain' => 'new.example.com',
            'type' => HostedDomainType::ALIAS->value,
            'ssl_method' => SslMethod::NONE->value,
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('hosted_domains', [
            'id' => $domain->id,
            'domain' => 'new.example.com',
        ]);
    }

    public function test_cannot_update_primary_domain_name(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => 'primary.example.com',
            'status' => HostedDomainStatus::ACTIVE,
            'ssl_method' => SslMethod::NONE,
        ]);

        $this->put(route('hosted-domains.update', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]), [
            'domain' => 'changed.example.com',
            'type' => HostedDomainType::ALIAS->value,
            'ssl_method' => SslMethod::NONE->value,
        ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('hosted_domains', [
            'id' => $domain->id,
            'domain' => 'primary.example.com',
        ]);
    }

    public function test_cannot_update_processing_domain(): void
    {
        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'processing.example.com',
            'status' => HostedDomainStatus::CREATING,
            'ssl_method' => SslMethod::NONE,
        ]);

        $this->put(route('hosted-domains.update', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]), [
            'domain' => 'new.example.com',
            'type' => HostedDomainType::ALIAS->value,
            'ssl_method' => SslMethod::NONE->value,
        ])
            ->assertSessionHasErrors('domain');
    }

    public function test_delete_domain(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'delete-me.example.com',
            'status' => HostedDomainStatus::ACTIVE,
        ]);

        $this->delete(route('hosted-domains.destroy', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('hosted_domains', ['id' => $domain->id]);
    }

    public function test_cannot_delete_primary_domain(): void
    {
        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => 'primary.example.com',
            'status' => HostedDomainStatus::ACTIVE,
        ]);

        $this->delete(route('hosted-domains.destroy', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]))
            ->assertSessionHasErrors('domain');

        $this->assertDatabaseHas('hosted_domains', ['id' => $domain->id]);
    }

    public function test_cannot_delete_processing_domain(): void
    {
        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'processing.example.com',
            'status' => HostedDomainStatus::CREATING,
        ]);

        $this->delete(route('hosted-domains.destroy', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]))
            ->assertSessionHasErrors('domain');

        $this->assertDatabaseHas('hosted_domains', ['id' => $domain->id]);
    }

    public function test_force_activate_domain(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'pending.example.com',
            'status' => HostedDomainStatus::PENDING,
            'ssl_method' => SslMethod::NONE,
        ]);

        $this->post(route('hosted-domains.force-activate', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('hosted_domains', [
            'id' => $domain->id,
            'status' => HostedDomainStatus::ACTIVE,
        ]);
    }

    public function test_deactivate_domain(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'active.example.com',
            'status' => HostedDomainStatus::ACTIVE,
        ]);

        $this->post(route('hosted-domains.deactivate', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]))
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('hosted_domains', [
            'id' => $domain->id,
            'status' => HostedDomainStatus::INACTIVE,
        ]);
    }

    public function test_cannot_deactivate_primary_domain(): void
    {
        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => 'primary.example.com',
            'status' => HostedDomainStatus::ACTIVE,
        ]);

        $this->post(route('hosted-domains.deactivate', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]))
            ->assertSessionHasErrors('domain');
    }

    public function test_reactivate_inactive_domain(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'inactive.example.com',
            'status' => HostedDomainStatus::INACTIVE,
        ]);

        $this->post(route('hosted-domains.reactivate', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]))
            ->assertRedirect();

        $this->assertDatabaseHas('hosted_domains', [
            'id' => $domain->id,
            'status' => HostedDomainStatus::UPDATING,
        ]);

        Bus::assertDispatched(CheckDomainJob::class);
    }

    public function test_cannot_reactivate_active_domain(): void
    {
        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'active.example.com',
            'status' => HostedDomainStatus::ACTIVE,
        ]);

        $this->post(route('hosted-domains.reactivate', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]))
            ->assertSessionHasErrors('domain');
    }

    public function test_check_dns(): void
    {
        Bus::fake();

        $this->actingAs($this->user);

        $domain = HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'check.example.com',
            'status' => HostedDomainStatus::ACTIVE,
        ]);

        $this->post(route('hosted-domains.check-dns', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'hostedDomain' => $domain->id,
        ]))
            ->assertRedirect();

        $this->assertDatabaseHas('hosted_domains', [
            'id' => $domain->id,
            'status' => HostedDomainStatus::UPDATING,
        ]);

        Bus::assertDispatched(CheckDomainJob::class);
    }

    public function test_matching_ssls_returns_matches(): void
    {
        $this->actingAs($this->user);

        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'domains' => ['*.example.com'],
        ]);

        $this->getJson(route('hosted-domains.matching-ssls', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'domain' => 'sub.example.com',
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1, 'certificates');
    }

    public function test_matching_ssls_returns_empty_for_invalid_domain(): void
    {
        $this->actingAs($this->user);

        $this->getJson(route('hosted-domains.matching-ssls', [
            'server' => $this->server->id,
            'site' => $this->site->id,
            'domain' => 'not valid',
        ]))
            ->assertSuccessful()
            ->assertJsonCount(0, 'certificates');
    }
}
