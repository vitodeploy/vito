<?php

namespace Tests\Unit\Actions\SSL;

use App\Actions\SSL\GetMatchingSslCertificates;
use App\Enums\SslStatus;
use App\Models\HostedDomain;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMatchingSslCertificatesTest extends TestCase
{
    use RefreshDatabase;

    private GetMatchingSslCertificates $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new GetMatchingSslCertificates;
    }

    public function test_matches_exact_domain(): void
    {
        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'domains' => ['app.example.com'],
        ]);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'app.example.com',
        ]);

        $result = $this->action->get($this->site);

        $this->assertCount(1, $result);
    }

    public function test_matches_wildcard_domain(): void
    {
        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'domains' => ['*.example.com'],
        ]);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'sub.example.com',
        ]);

        $result = $this->action->get($this->site);

        $this->assertCount(1, $result);
    }

    public function test_does_not_match_unrelated_domain(): void
    {
        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'domains' => ['other.com'],
        ]);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'app.example.com',
        ]);

        $result = $this->action->get($this->site);

        $this->assertCount(0, $result);
    }

    public function test_excludes_non_created_ssls(): void
    {
        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATING,
            'domains' => ['app.example.com'],
        ]);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'app.example.com',
        ]);

        $result = $this->action->get($this->site);

        $this->assertCount(0, $result);
    }

    public function test_excludes_site_level_ssls(): void
    {
        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => $this->site->id,
            'status' => SslStatus::CREATED,
            'domains' => ['app.example.com'],
        ]);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'app.example.com',
        ]);

        $result = $this->action->get($this->site);

        $this->assertCount(0, $result);
    }

    public function test_for_domain_matches_specific_domain(): void
    {
        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'domains' => ['*.example.com'],
        ]);

        $result = $this->action->forDomain($this->site, 'sub.example.com');

        $this->assertCount(1, $result);
    }

    public function test_for_domain_does_not_match_unrelated(): void
    {
        Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'domains' => ['*.other.com'],
        ]);

        $result = $this->action->forDomain($this->site, 'sub.example.com');

        $this->assertCount(0, $result);
    }

    public function test_returns_formatted_result(): void
    {
        $ssl = Ssl::factory()->create([
            'server_id' => $this->server->id,
            'site_id' => null,
            'status' => SslStatus::CREATED,
            'type' => 'letsencrypt',
            'domains' => ['*.example.com', 'example.com'],
        ]);

        HostedDomain::factory()->create([
            'site_id' => $this->site->id,
            'domain' => 'app.example.com',
        ]);

        $result = $this->action->get($this->site);

        $this->assertCount(1, $result);
        $item = $result->first();
        $this->assertEquals($ssl->id, $item['id']);
        $this->assertArrayHasKey('label', $item);
        $this->assertEquals(['*.example.com', 'example.com'], $item['domains']);
    }
}
