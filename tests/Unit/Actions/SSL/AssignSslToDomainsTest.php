<?php

use App\Actions\SSL\AssignSslToDomains;
use App\Enums\HostedDomainType;
use App\Enums\SslStatus;
use App\Models\HostedDomain;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new AssignSslToDomains;
});

test('exact match assigns correctly', function () {
    $ssl = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl(['domains' => ['app.example.com']]);
    $domain = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('app.example.com');

    $changed = $this->action->assign($this->site);

    expect($changed)->toHaveCount(1);
    expect($domain->fresh()->ssl_id)->toEqual($ssl->id);
});

test('wildcard match assigns correctly', function () {
    $ssl = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl(['domains' => ['*.example.com']]);
    $domain = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('sub.example.com');

    $changed = $this->action->assign($this->site);

    expect($changed)->toHaveCount(1);
    expect($domain->fresh()->ssl_id)->toEqual($ssl->id);
});

test('wildcard does not match bare domain', function () {
    vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl(['domains' => ['*.example.com']]);
    $domain = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('example.com');

    $changed = $this->action->assign($this->site);

    expect($changed)->toHaveCount(0);
    expect($domain->fresh()->ssl_id)->toBeNull();
});

test('wildcard does not match nested subdomain', function () {
    vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl(['domains' => ['*.example.com']]);
    $domain = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('a.b.example.com');

    $changed = $this->action->assign($this->site);

    expect($changed)->toHaveCount(0);
    expect($domain->fresh()->ssl_id)->toBeNull();
});

test('exact takes priority over wildcard', function () {
    $wildcardSsl = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl(['domains' => ['*.example.com']]);
    $exactSsl = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl(['domains' => ['app.example.com']]);
    $domain = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('app.example.com');

    $changed = $this->action->assign($this->site);

    expect($changed)->toHaveCount(1);
    expect($domain->fresh()->ssl_id)->toEqual($exactSsl->id);
});

test('no match leaves ssl id null', function () {
    vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl(['domains' => ['other.com']]);
    $domain = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('app.example.com');

    $changed = $this->action->assign($this->site);

    expect($changed)->toHaveCount(0);
    expect($domain->fresh()->ssl_id)->toBeNull();
});

test('only server level ssls considered', function () {
    Ssl::factory()->create([
        'site_id' => $this->site->id,
        'domains' => ['app.example.com'],
        'status' => SslStatus::CREATED,
    ]);
    $domain = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('app.example.com');

    $changed = $this->action->assign($this->site);

    expect($changed)->toHaveCount(0);
    expect($domain->fresh()->ssl_id)->toBeNull();
});

test('non created status ssls not considered', function () {
    vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl([
        'domains' => ['app.example.com'],
        'status' => SslStatus::CREATING,
    ]);
    $domain = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('app.example.com');

    $changed = $this->action->assign($this->site);

    expect($changed)->toHaveCount(0);
    expect($domain->fresh()->ssl_id)->toBeNull();
});

test('returns only changed domains', function () {
    $ssl = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl(['domains' => ['app.example.com']]);

    $alreadyAssigned = vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('app.example.com');
    $alreadyAssigned->update(['ssl_id' => $ssl->id]);

    vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain('other.com');

    $changed = $this->action->assign($this->site);

    expect($changed)->toHaveCount(0);
});

function vitoPestUnitActionsSSLAssignSslToDomainsTestCreateServerSsl(array $attributes = []): Ssl
{
    return Ssl::factory()->create(array_merge([
        'site_id' => null,
        'server_id' => test()->server->id,
        'status' => SslStatus::CREATED,
        'domains' => ['example.com'],
    ], $attributes));
}

function vitoPestUnitActionsSSLAssignSslToDomainsTestCreateHostedDomain(string $domain, HostedDomainType $type = HostedDomainType::ALIAS): HostedDomain
{
    return HostedDomain::factory()->create([
        'site_id' => test()->site->id,
        'domain' => $domain,
        'type' => $type,
    ]);
}
