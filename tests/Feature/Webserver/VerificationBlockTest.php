<?php

namespace Tests\Feature\Webserver;

use App\Actions\Webserver\GenerateNginxConfig;
use App\Enums\ServiceStatus;
use App\Models\HostedDomain;
use App\Models\Service;
use App\Services\Webserver\Caddy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_nginx_renders_verification_location_when_key_present(): void
    {
        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $this->site->verification_key = 'abc123XYZverification';
        $this->site->save();

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString('location ^~ /.well-known/vito/abc123XYZverification/', $vhost);
        $this->assertStringContainsString('alias /var/lib/vito/verify/abc123XYZverification/', $vhost);
        $this->assertStringContainsString('add_header Cache-Control "no-store"', $vhost);
    }

    public function test_nginx_omits_verification_location_when_key_missing(): void
    {
        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $this->site->verification_key = null;
        $this->site->vhost_template = null;
        $this->site->vhost_generation_enabled = false;
        $this->site->save();

        $vhost = app(GenerateNginxConfig::class)->generate($this->site);

        $this->assertStringNotContainsString('/.well-known/vito/', $vhost);
    }

    public function test_nginx_acme_location_renders_outside_basic_auth(): void
    {
        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $vhost = $this->site->webserver()->generateVhost($this->site);

        $this->assertStringContainsString('location ^~ /.well-known/acme-challenge/', $vhost);
    }

    public function test_caddy_renders_verification_handle_when_key_present(): void
    {
        $this->server->services()->where('type', 'webserver')->delete();
        $this->server->services()->create([
            'type' => Caddy::type(),
            'name' => Caddy::id(),
            'version' => 'latest',
        ]);
        $this->server->services()->update([
            'status' => ServiceStatus::READY,
        ]);
        $this->server->refresh();

        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $this->site->verification_key = 'caddyKey99';
        $this->site->save();

        /** @var Service $webserver */
        $webserver = $this->server->webserver();
        $vhost = $webserver->handler()->generateVhost($this->site);

        $this->assertStringContainsString('handle_path /.well-known/vito/caddyKey99/*', $vhost);
        $this->assertStringContainsString('root * /var/lib/vito/verify/caddyKey99', $vhost);
    }

    public function test_caddy_serves_verification_over_http_when_using_auto_https(): void
    {
        $this->switchToCaddy();

        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $this->site->update([
            'ssl_enabled' => true,
            'verification_key' => 'caddyForce42',
        ]);
        $this->site->refresh();

        $vhost = $this->server->webserver()->handler()->generateVhost($this->site);

        $this->assertStringContainsString('http://'.$this->site->domain.' {', $vhost);
        $this->assertStringContainsString('handle_path /.well-known/vito/caddyForce42/*', $vhost);
        $this->assertStringContainsString('redir https://{host}{uri} permanent', $vhost);
    }

    public function test_caddy_omits_http_verification_block_for_http_only_site(): void
    {
        $this->switchToCaddy();

        HostedDomain::factory()->primary()->create([
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
        ]);

        $this->site->update([
            'ssl_enabled' => false,
            'verification_key' => 'caddyPlain1',
        ]);
        $this->site->refresh();

        $vhost = $this->server->webserver()->handler()->generateVhost($this->site);

        $this->assertStringContainsString('handle_path /.well-known/vito/caddyPlain1/*', $vhost);
        $this->assertStringNotContainsString('redir https://{host}{uri} permanent', $vhost);
    }

    private function switchToCaddy(): void
    {
        $this->server->services()->where('type', 'webserver')->delete();
        $this->server->services()->create([
            'type' => Caddy::type(),
            'name' => Caddy::id(),
            'version' => 'latest',
            'status' => ServiceStatus::READY,
        ]);
        $this->server->refresh();
    }
}
