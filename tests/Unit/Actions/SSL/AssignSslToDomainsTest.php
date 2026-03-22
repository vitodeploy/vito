<?php

namespace Tests\Unit\Actions\SSL;

use App\Actions\SSL\AssignSslToDomains;
use App\Enums\HostedDomainType;
use App\Enums\SslStatus;
use App\Models\HostedDomain;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignSslToDomainsTest extends TestCase
{
    use RefreshDatabase;

    private AssignSslToDomains $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new AssignSslToDomains;
    }

    public function test_exact_match_assigns_correctly(): void
    {
        $ssl = $this->createServerSsl(['domains' => ['app.example.com']]);
        $domain = $this->createHostedDomain('app.example.com');

        $changed = $this->action->assign($this->site);

        $this->assertCount(1, $changed);
        $this->assertEquals($ssl->id, $domain->fresh()->ssl_id);
    }

    public function test_wildcard_match_assigns_correctly(): void
    {
        $ssl = $this->createServerSsl(['domains' => ['*.example.com']]);
        $domain = $this->createHostedDomain('sub.example.com');

        $changed = $this->action->assign($this->site);

        $this->assertCount(1, $changed);
        $this->assertEquals($ssl->id, $domain->fresh()->ssl_id);
    }

    public function test_wildcard_does_not_match_bare_domain(): void
    {
        $this->createServerSsl(['domains' => ['*.example.com']]);
        $domain = $this->createHostedDomain('example.com');

        $changed = $this->action->assign($this->site);

        $this->assertCount(0, $changed);
        $this->assertNull($domain->fresh()->ssl_id);
    }

    public function test_wildcard_does_not_match_nested_subdomain(): void
    {
        $this->createServerSsl(['domains' => ['*.example.com']]);
        $domain = $this->createHostedDomain('a.b.example.com');

        $changed = $this->action->assign($this->site);

        $this->assertCount(0, $changed);
        $this->assertNull($domain->fresh()->ssl_id);
    }

    public function test_exact_takes_priority_over_wildcard(): void
    {
        $wildcardSsl = $this->createServerSsl(['domains' => ['*.example.com']]);
        $exactSsl = $this->createServerSsl(['domains' => ['app.example.com']]);
        $domain = $this->createHostedDomain('app.example.com');

        $changed = $this->action->assign($this->site);

        $this->assertCount(1, $changed);
        $this->assertEquals($exactSsl->id, $domain->fresh()->ssl_id);
    }

    public function test_no_match_leaves_ssl_id_null(): void
    {
        $this->createServerSsl(['domains' => ['other.com']]);
        $domain = $this->createHostedDomain('app.example.com');

        $changed = $this->action->assign($this->site);

        $this->assertCount(0, $changed);
        $this->assertNull($domain->fresh()->ssl_id);
    }

    public function test_only_server_level_ssls_considered(): void
    {
        Ssl::factory()->create([
            'site_id' => $this->site->id,
            'domains' => ['app.example.com'],
            'status' => SslStatus::CREATED,
        ]);
        $domain = $this->createHostedDomain('app.example.com');

        $changed = $this->action->assign($this->site);

        $this->assertCount(0, $changed);
        $this->assertNull($domain->fresh()->ssl_id);
    }

    public function test_non_created_status_ssls_not_considered(): void
    {
        $this->createServerSsl([
            'domains' => ['app.example.com'],
            'status' => SslStatus::CREATING,
        ]);
        $domain = $this->createHostedDomain('app.example.com');

        $changed = $this->action->assign($this->site);

        $this->assertCount(0, $changed);
        $this->assertNull($domain->fresh()->ssl_id);
    }

    public function test_returns_only_changed_domains(): void
    {
        $ssl = $this->createServerSsl(['domains' => ['app.example.com']]);

        $alreadyAssigned = $this->createHostedDomain('app.example.com');
        $alreadyAssigned->update(['ssl_id' => $ssl->id]);

        $this->createHostedDomain('other.com');

        $changed = $this->action->assign($this->site);

        $this->assertCount(0, $changed);
    }

    private function createServerSsl(array $attributes = []): Ssl
    {
        return Ssl::factory()->create(array_merge([
            'site_id' => null,
            'server_id' => $this->server->id,
            'status' => SslStatus::CREATED,
            'domains' => ['example.com'],
        ], $attributes));
    }

    private function createHostedDomain(string $domain, HostedDomainType $type = HostedDomainType::ALIAS): HostedDomain
    {
        return HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => $domain,
            'type' => $type,
        ]);
    }
}
