<?php

use App\Actions\Webserver\GenerateNginxConfig;
use App\Enums\HostedDomainStatus;
use App\Enums\ServiceStatus;
use App\Enums\SslStatus;
use App\Models\HostedDomain;
use App\Models\Service;
use App\Models\Ssl;
use App\Services\Webserver\Caddy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('nginx renders verification location when key present', function () {
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
});

test('nginx omits verification location when key missing', function () {
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
});

test('nginx acme location renders outside basic auth', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringContainsString('location ^~ /.well-known/acme-challenge/', $vhost);
});

test('caddy renders verification handle when key present', function () {
    vitoPestFeatureWebserverVerificationBlockTestSwitchToCaddy();

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
});

test('nginx force ssl redirect serves and exempts verification challenge', function () {
    $ssl = Ssl::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'status' => SslStatus::CREATED,
        'type' => 'letsencrypt',
        'domains' => [$this->site->domain],
    ]);

    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
        'ssl_id' => $ssl->id,
    ]);

    $this->site->update([
        'ssl_enabled' => true,
        'force_ssl' => true,
        'verification_key' => 'forcedKey55',
    ]);
    $this->site->refresh();

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringContainsString('location ^~ /.well-known/vito/forcedKey55/', $vhost);
    $this->assertStringContainsString('alias /var/lib/vito/verify/forcedKey55/', $vhost);
    $this->assertStringContainsString(
        "location / {\n        return 301 https://\$host\$request_uri;\n    }",
        $vhost,
        'The force-SSL port-80 redirect must be scoped to location / so the verification path is served instead of 301-redirected.'
    );
});

test('caddy serves verification over http when using auto https', function () {
    vitoPestFeatureWebserverVerificationBlockTestSwitchToCaddy();

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
});

test('caddy omits http verification block for http only site', function () {
    vitoPestFeatureWebserverVerificationBlockTestSwitchToCaddy();

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
});

test('nginx serves the challenge for pending alias domains before they are active', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);
    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'pending.example.com',
        'status' => HostedDomainStatus::PENDING,
    ]);

    $this->site->update(['verification_key' => 'pendingKey77']);
    $this->site->refresh();

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertMatchesRegularExpression('/server_name pending\.example\.com;/', $vhost);
    $this->assertStringContainsString('location ^~ /.well-known/vito/pendingKey77/', $vhost);
    $this->assertStringNotContainsString('server_name '.$this->site->domain.' pending.example.com', $vhost);
});

test('nginx omits the pending challenge block when every domain is active', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);
    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'active.example.com',
        'status' => HostedDomainStatus::ACTIVE,
    ]);

    $this->site->update(['verification_key' => 'activeKey88']);
    $this->site->refresh();

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringNotContainsString('server_name active.example.com;', $vhost);
    $this->assertStringNotContainsString('location / {
        return 404;', $vhost);
});

test('nginx keeps an updating alias in the site server block instead of the challenge block', function () {
    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);
    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'live.example.com',
        'status' => HostedDomainStatus::UPDATING,
    ]);

    $this->site->update(['verification_key' => 'updatingKey11']);
    $this->site->refresh();

    $vhost = $this->site->webserver()->generateVhost($this->site);

    $this->assertStringContainsString('server_name '.$this->site->domain.' live.example.com;', $vhost);
    $this->assertStringNotContainsString('server_name live.example.com;', $vhost);
});

test('caddy serves the challenge for pending alias domains before they are active', function () {
    vitoPestFeatureWebserverVerificationBlockTestSwitchToCaddy();

    HostedDomain::factory()->primary()->create([
        'site_id' => $this->site->id,
        'domain' => $this->site->domain,
    ]);
    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'pending.example.com',
        'status' => HostedDomainStatus::CREATING,
    ]);

    $this->site->update(['verification_key' => 'caddyPending9']);
    $this->site->refresh();

    $vhost = $this->server->webserver()->handler()->generateVhost($this->site);

    $this->assertStringContainsString('http://pending.example.com {', $vhost);
    $this->assertStringContainsString('respond 404', $vhost);
});

function vitoPestFeatureWebserverVerificationBlockTestSwitchToCaddy(): void
{
    test()->server->services()->where('type', 'webserver')->delete();
    test()->server->services()->create([
        'type' => Caddy::type(),
        'name' => Caddy::id(),
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);
    test()->server->refresh();
}
