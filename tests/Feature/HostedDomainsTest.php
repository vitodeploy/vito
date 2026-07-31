<?php

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Enums\SslType;
use App\Facades\Notifier;
use App\Facades\SSH;
use App\Jobs\HostedDomain\CheckDomainJob;
use App\Models\HostedDomain;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see hosted domains list', function () {
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
});

test('create alias domain', function () {
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
});

test('create redirect domain', function () {
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
});

test('create domain validation requires fields', function () {
    $this->actingAs($this->user);

    $this->post(route('hosted-domains.store', [
        'server' => $this->server->id,
        'site' => $this->site->id,
    ]), [])
        ->assertSessionHasErrors(['domain', 'type', 'ssl_method']);
});

test('create domain validation invalid domain format', function () {
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
});

test('create domain rejects primary type', function () {
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
});

test('create domain rejects duplicate on same server', function () {
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
});

test('create domain with custom ssl', function () {
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
});

test('create domain custom ssl requires ssl id', function () {
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
});

test('update domain', function () {
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
});

test('cannot update primary domain name', function () {
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
});

test('cannot update processing domain', function () {
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
});

test('delete domain', function () {
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
});

test('cannot delete primary domain', function () {
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
});

test('cannot delete processing domain', function () {
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
});

test('force activate domain', function () {
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
});

test('deactivate domain', function () {
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
});

test('cannot deactivate primary domain', function () {
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
});

test('reactivate inactive domain', function () {
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
});

test('cannot reactivate active domain', function () {
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
});

test('check dns', function () {
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
});

test('matching ssls returns matches', function () {
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
});

test('matching ssls returns empty for invalid domain', function () {
    $this->actingAs($this->user);

    $this->getJson(route('hosted-domains.matching-ssls', [
        'server' => $this->server->id,
        'site' => $this->site->id,
        'domain' => 'not valid',
    ]))
        ->assertSuccessful()
        ->assertJsonCount(0, 'certificates');
});

test('check expiry refreshes date without notifying', function () {
    SSH::fake(vitoPestFeatureHostedDomainsTestGenerateTestCertificate(90));
    Notifier::spy();

    $this->actingAs($this->user);

    $ssl = Ssl::factory()->create([
        'site_id' => $this->site->id,
        'type' => SslType::LETSENCRYPT,
        'status' => SslStatus::CREATED,
        'certificate_path' => '/etc/letsencrypt/live/1/fullchain.pem',
        'expires_at' => now()->addDays(5),
        'domains' => ['old.example.com'],
    ]);

    $hostedDomain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'ssl_id' => $ssl->id,
    ]);

    $this->post(route('hosted-domains.check-expiry', [
        'server' => $this->server->id,
        'site' => $this->site->id,
        'hostedDomain' => $hostedDomain->id,
    ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $ssl->refresh();
    expect($ssl->expires_at->isAfter(now()->addDays(80)))->toBeTrue();
    expect($ssl->domains)->toEqualCanonicalizing(['example.com', 'www.example.com']);
    expect($ssl->domains)->not->toContain('old.example.com');
    Notifier::shouldNotHaveReceived('send');
});

test('check expiry errors when domain has no ssl', function () {
    $this->actingAs($this->user);

    $hostedDomain = HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'ssl_id' => null,
    ]);

    $this->post(route('hosted-domains.check-expiry', [
        'server' => $this->server->id,
        'site' => $this->site->id,
        'hostedDomain' => $hostedDomain->id,
    ]))
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('check expiry all refreshes every site certificate', function () {
    SSH::fake(vitoPestFeatureHostedDomainsTestGenerateTestCertificate(90));
    Notifier::spy();

    $this->actingAs($this->user);

    $sslOne = Ssl::factory()->create([
        'site_id' => $this->site->id,
        'type' => SslType::LETSENCRYPT,
        'status' => SslStatus::CREATED,
        'certificate_path' => '/etc/letsencrypt/live/1/fullchain.pem',
        'expires_at' => now()->addDays(5),
        'domains' => ['old.example.com'],
    ]);

    $sslTwo = Ssl::factory()->create([
        'site_id' => $this->site->id,
        'type' => SslType::LETSENCRYPT,
        'status' => SslStatus::CREATED,
        'certificate_path' => '/etc/letsencrypt/live/2/fullchain.pem',
        'expires_at' => now()->addDays(5),
        'domains' => ['old.example.com'],
    ]);

    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'ssl_id' => $sslOne->id,
    ]);

    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'ssl_id' => $sslTwo->id,
    ]);

    HostedDomain::factory()->create([
        'site_id' => $this->site->id,
        'ssl_id' => $sslOne->id,
    ]);

    $this->post(route('hosted-domains.check-expiry-all', [
        'server' => $this->server->id,
        'site' => $this->site->id,
    ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    foreach ([$sslOne, $sslTwo] as $ssl) {
        $ssl->refresh();
        expect($ssl->expires_at->isAfter(now()->addDays(80)))->toBeTrue();
        expect($ssl->domains)->not->toContain('old.example.com');
    }

    Notifier::shouldNotHaveReceived('send');
});

function vitoPestFeatureHostedDomainsTestGenerateTestCertificate(int $validDays = 90): string
{
    $config = tempnam(sys_get_temp_dir(), 'openssl');
    file_put_contents($config, <<<'INI'
[req]
distinguished_name = req_dn
req_extensions = v3_req

[req_dn]
CN = example.com

[v3_req]
subjectAltName = DNS:example.com,DNS:www.example.com
INI);

    $key = openssl_pkey_new(['private_key_bits' => 2048, 'config' => $config]);
    $csr = openssl_csr_new(['CN' => 'example.com'], $key, ['config' => $config]);
    $cert = openssl_csr_sign($csr, null, $key, $validDays, [
        'config' => $config,
        'x509_extensions' => 'v3_req',
    ]);

    openssl_x509_export($cert, $certPem);

    return $certPem;
}
