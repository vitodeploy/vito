<?php

use App\Actions\HostedDomain\VerifyHostedDomain;
use App\Actions\Site\EnsureSiteVerificationKey;
use App\Enums\HostedDomainStatus;
use App\Facades\SSH;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('succeeds when server returns expected hmac', function () {
    SSH::fake();

    $domain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'verifies.example.com',
        'status' => HostedDomainStatus::PENDING,
    ]);

    Http::fake(function (Request $request) use ($domain) {
        $url = $request->url();

        if (str_contains($url, 'cloudflare-dns.com') || str_contains($url, 'dns.google')) {
            if (str_contains($url, 'type=A')) {
                return Http::response(['Answer' => [['data' => '203.0.113.10']]], 200);
            }

            return Http::response(['Answer' => []], 200);
        }

        $challengeId = basename(parse_url($url, PHP_URL_PATH));
        $key = app(EnsureSiteVerificationKey::class)->ensure($this->site->refresh());
        $expected = hash_hmac('sha256', implode("\n", [
            (string) $this->site->server_id,
            (string) $this->site->id,
            $domain->domain,
            $challengeId,
        ]), (string) config('app.key'));

        return Http::response($expected, 200);
    });

    $result = app(VerifyHostedDomain::class)->verify($domain);

    expect($result->verified)->toBeTrue();
});

test('fails when response body does not match hmac', function () {
    SSH::fake();

    $domain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'mismatched.example.com',
        'status' => HostedDomainStatus::PENDING,
    ]);

    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, 'cloudflare-dns.com') || str_contains($url, 'dns.google')) {
            return Http::response(['Answer' => str_contains($url, 'type=A') ? [['data' => '198.51.100.1']] : []], 200);
        }

        return Http::response('not the expected hmac', 200);
    });

    $result = app(VerifyHostedDomain::class)->verify($domain);

    expect($result->verified)->toBeFalse();
});

test('cross install secret rejects captured response', function () {
    SSH::fake();

    $domain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'cross-install.example.com',
        'status' => HostedDomainStatus::PENDING,
    ]);

    Http::fake(function (Request $request) use ($domain) {
        $url = $request->url();

        if (str_contains($url, 'cloudflare-dns.com') || str_contains($url, 'dns.google')) {
            return Http::response(['Answer' => str_contains($url, 'type=A') ? [['data' => '203.0.113.10']] : []], 200);
        }

        $challengeId = basename(parse_url($url, PHP_URL_PATH));
        $impostor = hash_hmac('sha256', implode("\n", [
            (string) $this->site->server_id,
            (string) $this->site->id,
            $domain->domain,
            $challengeId,
        ]), 'a-different-install-secret');

        return Http::response($impostor, 200);
    });

    $result = app(VerifyHostedDomain::class)->verify($domain);

    expect($result->verified)->toBeFalse();
});

test('generates a fresh challenge id per attempt', function () {
    SSH::fake();

    $domain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'fresh.example.com',
        'status' => HostedDomainStatus::PENDING,
    ]);

    $challengeIds = [];

    Http::fake(function (Request $request) use (&$challengeIds) {
        $url = $request->url();

        if (str_contains($url, 'cloudflare-dns.com') || str_contains($url, 'dns.google')) {
            return Http::response(['Answer' => str_contains($url, 'type=A') ? [['data' => '203.0.113.10']] : []], 200);
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (preg_match('#/\.well-known/vito/[^/]+/([a-f0-9]{32})$#', $path, $m)) {
            $challengeIds[] = $m[1];
        }

        return Http::response('mismatch', 200);
    });

    app(VerifyHostedDomain::class)->verify($domain);
    app(VerifyHostedDomain::class)->verify($domain);

    expect($challengeIds)->toHaveCount(2, 'expected exactly one verification fetch per verify()');
    $this->assertNotSame($challengeIds[0], $challengeIds[1]);
});

test('fails when no ips resolve', function () {
    SSH::fake();

    $domain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'noip.example.com',
        'status' => HostedDomainStatus::PENDING,
    ]);

    Http::fake([
        'cloudflare-dns.com/*' => Http::response(['Answer' => []], 200),
        'dns.google/*' => Http::response(['Answer' => []], 200),
    ]);

    $result = app(VerifyHostedDomain::class)->verify($domain);

    expect($result->verified)->toBeFalse();
    $this->assertStringContainsString('HTTP', $result->failureReason);
});

test('publishes the challenge route in the site vhost before verifying a pending domain', function () {
    $ssh = SSH::fake();
    Http::fake(fn () => Http::response(['Answer' => []], 200));

    $domain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'unpublished.example.com',
        'status' => HostedDomainStatus::CREATING,
    ]);

    app(VerifyHostedDomain::class)->verify($domain);

    $ssh->assertExecutedContains('/etc/nginx/sites-available/'.$this->site->domain);
    expect($ssh->getUploadedContent())
        ->toContain('server_name unpublished.example.com;')
        ->toContain('/.well-known/vito/');
});

test('does not rewrite the vhost when verifying an already active domain', function () {
    $ssh = SSH::fake();
    Http::fake(fn () => Http::response(['Answer' => []], 200));

    $domain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'live.example.com',
        'status' => HostedDomainStatus::ACTIVE,
    ]);

    app(VerifyHostedDomain::class)->verify($domain);

    $ssh->assertNotExecutedContains('/etc/nginx/sites-available/');
});

test('does not rewrite the vhost when it already has the challenge route', function () {
    Http::fake(fn () => Http::response(['Answer' => []], 200));

    $domain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'already.example.com',
        'status' => HostedDomainStatus::PENDING,
    ]);

    $this->site->refresh();
    app(EnsureSiteVerificationKey::class)->ensure($this->site);
    $current = $this->site->webserver()->generateVhost($this->site->refresh());

    $ssh = SSH::fake($current);

    app(VerifyHostedDomain::class)->verify($domain);

    $ssh->assertNotExecutedContains('reload');
});

test('takes a renamed alias back out of the site server block when its check fails', function () {
    $ssh = SSH::fake();
    Http::fake(fn () => Http::response(['Answer' => []], 200));

    $domain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'domain' => 'renamed.example.com',
        'status' => HostedDomainStatus::UPDATING,
    ]);

    app()->call([new CheckDomainJob($domain), 'handle']);

    expect($domain->refresh()->status)->toBe(HostedDomainStatus::PENDING);
    expect($ssh->getUploadedContent())
        ->toContain('server_name renamed.example.com;')
        ->not->toContain(' renamed.example.com renamed.example.com');
    $this->assertStringNotContainsString('server_name '.$this->site->domain.' renamed.example.com', $ssh->getUploadedContent());
});
