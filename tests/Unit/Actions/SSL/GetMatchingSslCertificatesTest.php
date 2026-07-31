<?php

use App\Actions\SSL\GetMatchingSslCertificates;
use App\Enums\SslStatus;
use App\Models\HostedDomain;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new GetMatchingSslCertificates;
});

test('matches exact domain', function () {
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

    expect($result)->toHaveCount(1);
});

test('matches wildcard domain', function () {
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

    expect($result)->toHaveCount(1);
});

test('does not match unrelated domain', function () {
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

    expect($result)->toHaveCount(0);
});

test('excludes non created ssls', function () {
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

    expect($result)->toHaveCount(0);
});

test('excludes site level ssls', function () {
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

    expect($result)->toHaveCount(0);
});

test('for domain matches specific domain', function () {
    Ssl::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => null,
        'status' => SslStatus::CREATED,
        'domains' => ['*.example.com'],
    ]);

    $result = $this->action->forDomain($this->site, 'sub.example.com');

    expect($result)->toHaveCount(1);
});

test('for domain does not match unrelated', function () {
    Ssl::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => null,
        'status' => SslStatus::CREATED,
        'domains' => ['*.other.com'],
    ]);

    $result = $this->action->forDomain($this->site, 'sub.example.com');

    expect($result)->toHaveCount(0);
});

test('returns formatted result', function () {
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

    expect($result)->toHaveCount(1);
    $item = $result->first();
    expect($item['id'])->toEqual($ssl->id);
    expect($item)->toHaveKey('label');
    expect($item['domains'])->toEqual(['*.example.com', 'example.com']);
});
