<?php

use App\Actions\Application\DeleteApplication;
use App\Actions\Component\SyncComponentVhost;
use App\Actions\Environment\DeleteEnvironment;
use App\Actions\EnvironmentDomain\ActivateEnvironmentDomain;
use App\Actions\EnvironmentDomain\CheckEnvironmentDomain;
use App\Actions\EnvironmentDomain\DeleteEnvironmentDomain;
use App\Actions\EnvironmentDomain\UpdateEnvironmentDomain;
use App\Actions\SSL\DeleteSsl;
use App\Actions\SSL\RenewEnvironmentSsl;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\EnvironmentStatus;
use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\ServerStatus;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Events\SocketEvent;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSLCreationException;
use App\Facades\SSH;
use App\Jobs\Application\DeleteApplicationJob;
use App\Jobs\Component\DeleteComponentJob;
use App\Jobs\Environment\DeleteEnvironmentJob;
use App\Jobs\EnvironmentDomain\CheckEnvironmentDomainJob;
use App\Jobs\EnvironmentDomain\DeleteEnvironmentDomainsJob;
use App\Jobs\SSL\CheckSslExpiryJob;
use App\Jobs\SSL\CreateLetsEncryptWildcardSslJob;
use App\Jobs\SSL\DeleteServerSslJob;
use App\Jobs\SSL\RenewEnvironmentSslJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\DNSProvider;
use App\Models\Domain;
use App\Models\Environment;
use App\Models\EnvironmentDomain;
use App\Models\HostedDomain;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    SSH::fake();
    Http::preventStrayRequests();
    Queue::fake();
    Event::fake([SocketEvent::class]);
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id]);
    $this->environment = Environment::factory()->for($this->application)->create(['runtime_version' => '8.2']);
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web', 'status' => ComponentStatus::READY, 'config' => ['port' => 10000, 'web_directory' => 'public']]);
    $this->domain = EnvironmentDomain::factory()->for($this->component)->create(['host' => 'app.example.com']);
    $this->verify = function (bool $success = true): void {
        Http::fake(function (Request $request) use ($success) {
            if (str_contains($request->url(), 'cloudflare-dns.com') || str_contains($request->url(), 'dns.google')) {
                return Http::response(['Answer' => [['data' => '203.0.113.10']]]);
            }
            $body = hash_hmac('sha256', implode("\n", [
                (string) $this->server->id, 'environment:'.$this->environment->id,
                parse_url($request->url(), PHP_URL_HOST), basename(parse_url($request->url(), PHP_URL_PATH)),
            ]), (string) config('app.key'));

            return Http::response($success ? $body : 'wrong installation');
        });
    };
    $this->certificate = fn (array $attributes = []): Ssl => Ssl::factory()->create(array_replace([
        'site_id' => null, 'server_id' => $this->server->id, 'environment_id' => $this->environment->id,
        'type' => 'letsencrypt', 'domains' => ['app.example.com', 'www.example.com'], 'expires_at' => now()->addDays(60),
    ], $attributes));
});

function vitoEnvironmentDomainTestCertificate(): string
{
    $config = tempnam(sys_get_temp_dir(), 'vito-domain-cert');
    file_put_contents($config, "[req]\ndistinguished_name = dn\n[dn]\nCN = app.example.com\n[v3_req]\nsubjectAltName = DNS:app.example.com,DNS:www.example.com\n");
    try {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new(['CN' => 'app.example.com'], $key, ['config' => $config]);
        $certificate = openssl_csr_sign($csr, null, $key, 90, ['config' => $config, 'x509_extensions' => 'v3_req']);
        openssl_x509_export($certificate, $pem);

        return $pem;
    } finally {
        unlink($config);
    }
}

test('component vhosts render each runtime mode without site or framework coupling', function (string $runtime, string $webserver) {
    $this->application->update(['runtime' => $runtime]);
    $this->server->services()->where('type', 'webserver')->update(['name' => $webserver]);
    $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE])->save();
    $vhost = app(SyncComponentVhost::class)->generate($this->component->fresh());

    expect($vhost)->toContain('app.example.com')->toContain('/.well-known/vito/'.app(SyncComponentVhost::class)->verificationKey($this->environment));
    if ($runtime === 'node') {
        expect($vhost)->toContain('127.0.0.1:10000')->not->toContain('fastcgi');
    } elseif ($runtime === 'php') {
        expect($vhost)->toContain('php8.2-fpm.sock')->toContain($this->component->currentPath().'/public');
    } else {
        expect($vhost)->toContain($this->component->currentPath().'/public')->not->toContain('fastcgi')->not->toContain('proxy');
    }
    app(SyncComponentVhost::class)->sync($this->component->fresh());
    expect(SSH::getUploadedContent())->toBe($vhost);
    SSH::assertExecutedContains('/etc/'.$webserver.'/sites-available/component-'.$this->component->id.'.pending');
    SSH::assertExecutedContains($webserver === 'nginx' ? 'nginx -t' : 'caddy validate');
    SSH::assertExecutedContains('trap rollback ERR');
})->with(['php', 'node', 'static'])->with(['nginx', 'caddy']);

test('caddy component routes deny hidden files without changing site verification handlers', function () {
    $this->application->update(['runtime' => 'static']);
    $this->component->capabilities()->where('capability', 'web')->update(['config' => ['web_directory' => '']]);
    $this->server->services()->where('type', 'webserver')->update(['name' => 'caddy']);
    $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE])->save();
    $vhost = app(SyncComponentVhost::class)->generate($this->component->fresh());
    expect($vhost)->toContain('root * '.$this->component->currentPath())->toContain('respond @hidden 404');
    preg_match('/@hidden path_regexp hidden (.+)/', $vhost, $match);
    foreach (['/.env', '/.git/config', '/nested/.env'] as $path) {
        expect(preg_match('~'.$match[1].'~', $path))->toBe(1);
    }
    expect(strpos($vhost, 'handle_path /.well-known/vito/'))->toBeLessThan(strpos($vhost, '@hidden'));
    HostedDomain::factory()->primary()->create(['site_id' => $this->site->id, 'domain' => $this->site->domain]);
    expect($this->site->fresh()->webserver()->generateVhost($this->site->fresh()))->not->toContain('@hidden');
});

test('isolated fpm vhosts use the component user socket', function () {
    $this->application->update(['runtime' => 'php']);
    $this->component->update(['user' => 'runtime']);
    $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE])->save();
    expect(app(SyncComponentVhost::class)->generate($this->component))->toContain('unix:/run/php/php8.2-fpm-runtime.sock');
});

test('force ssl requires a usable certificate and remains per domain', function (string $state, string $webserver) {
    $this->server->services()->where('type', 'webserver')->update(['name' => $webserver]);
    $ssl = ($this->certificate)([
        'status' => $state === 'failed' ? SslStatus::FAILED : SslStatus::CREATED,
        'expires_at' => $state === 'expired' ? now()->subDay() : now()->addDay(),
        'domains' => $state === 'uncovered' ? ['other.example.com'] : ['app.example.com'],
    ]);
    $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE, 'ssl_method' => SslMethod::LETSENCRYPT, 'ssl_id' => $ssl->id, 'force_ssl' => true])->save();
    EnvironmentDomain::factory()->for($this->component)->create(['host' => 'plain.example.com', 'status' => HostedDomainStatus::ACTIVE, 'force_ssl' => true]);
    $vhost = app(SyncComponentVhost::class)->generate($this->component->fresh());
    if ($state === 'valid') {
        expect($vhost)->toContain($ssl->certificate_path)->toContain($webserver === 'nginx' ? 'return 301 https://' : 'redir https://');
    } else {
        expect($vhost)->not->toContain($ssl->certificate_path);
        expect($vhost)->not->toContain($webserver === 'nginx' ? 'return 301 https://' : 'redir https://');
    }
    expect($vhost)->toContain('plain.example.com');
})->with(['valid', 'failed', 'expired', 'uncovered'])->with(['nginx', 'caddy']);

test('redirect vhosts follow active alias targets and preserve request paths', function (string $webserver) {
    $this->server->services()->where('type', 'webserver')->update(['name' => $webserver]);
    $ssl = ($this->certificate)(['environment_id' => null, 'type' => 'custom']);
    $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE, 'ssl_method' => SslMethod::CUSTOM, 'ssl_id' => $ssl->id, 'force_ssl' => true])->save();
    EnvironmentDomain::factory()->for($this->component)->create([
        'host' => 'redirect.example.com', 'type' => HostedDomainType::REDIRECT, 'redirect_to_domain_id' => $this->domain->id, 'status' => HostedDomainStatus::ACTIVE,
    ]);
    $sync = app(SyncComponentVhost::class);
    expect($sync->generate($this->component->fresh()))->toContain('https://app.example.com'.($webserver === 'nginx' ? '$request_uri' : '{uri}'));
    $this->domain->forceFill(['status' => HostedDomainStatus::PENDING])->save();
    expect($sync->generate($this->component))->toBe('');
})->with(['nginx', 'caddy']);

test('verification failure leaves hosts pending and never provisions ssl', function () {
    ($this->verify)(false);
    $this->domain->forceFill(['ssl_method' => SslMethod::LETSENCRYPT])->save();
    (new CheckEnvironmentDomainJob($this->domain->id, $this->server->id))->handle();
    expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::PENDING)->and($this->domain->fresh()->error)->not->toBeNull();
    $this->assertDatabaseCount('ssls', 0);
    expect(app(SyncComponentVhost::class)->generate($this->component))->toBe('');
    SSH::assertNotExecutedContains('certbot');
    SSH::assertExecutedContains('rm -f');
});

test('verified hosts activate without ssl or with target server lets encrypt', function (string $method, string $webserver) {
    $this->server->services()->where('type', 'webserver')->update(['name' => $webserver]);
    $this->domain->forceFill(['ssl_method' => $method, 'force_ssl' => true])->save();
    SSH::fake(vitoEnvironmentDomainTestCertificate()."\nSuccessfully received certificate");
    ($this->verify)();
    (new CheckEnvironmentDomainJob($this->domain->id, $this->server->id))->handle();

    $this->assertDatabaseHas('environment_domains', ['id' => $this->domain->id, 'status' => 'active', 'error' => null]);
    if ($method === 'letsencrypt' && $webserver === 'nginx') {
        $ssl = $this->domain->fresh()->ssl;
        expect($ssl->environment_id)->toBe($this->environment->id)->and($ssl->server_id)->toBe($this->server->id)->and($ssl->expires_at->isAfter(now()->addDays(80)))->toBeTrue();
        SSH::assertExecutedContains('certbot certonly');
    } else {
        $this->assertDatabaseCount('ssls', 0);
        SSH::assertNotExecutedContains('certbot');
    }
    $vhost = app(SyncComponentVhost::class)->generate($this->component->fresh());
    expect($vhost)->toContain('app.example.com');
    if ($webserver === 'caddy' && $method === 'letsencrypt') {
        expect($vhost)->toContain('https://app.example.com')->toContain('http://app.example.com');
    }
})->with(['none', 'letsencrypt'])->with(['nginx', 'caddy']);

test('custom certificate activation checks coverage expiry and server ownership after verification', function (string $state) {
    $ssl = ($this->certificate)([
        'environment_id' => null, 'type' => 'custom',
        'server_id' => $state === 'foreign' ? Server::factory()->create()->id : $this->server->id,
        'domains' => $state === 'uncovered' ? ['other.example.com'] : ['*.example.com'],
        'expires_at' => $state === 'expired' ? now()->subDay() : now()->addDay(),
    ]);
    $this->domain->forceFill(['ssl_method' => SslMethod::CUSTOM, 'ssl_id' => $ssl->id])->save();
    ($this->verify)();
    $check = fn () => app(CheckEnvironmentDomain::class)->check($this->domain->id, $this->server->id);
    if ($state === 'valid') {
        $check();
        expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::ACTIVE);
    } else {
        expect($check)->toThrow(ValidationException::class);
        expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::PENDING)->and($this->domain->fresh()->error)->not->toBeNull();
    }
    SSH::assertNotExecutedContains('certbot');
})->with(['valid', 'uncovered', 'expired', 'foreign']);

test('certificate failures preserve ownership for retry and last use cleanup', function () {
    $this->domain->forceFill(['ssl_method' => SslMethod::LETSENCRYPT])->save();
    ($this->verify)();
    expect(fn () => app(CheckEnvironmentDomain::class)->check($this->domain->id, $this->server->id))->toThrow(SSLCreationException::class);
    $ssl = $this->domain->fresh()->ssl;
    expect($ssl)->not->toBeNull()->and($ssl->status)->toBe(SslStatus::FAILED)->and($this->domain->fresh()->status)->toBe(HostedDomainStatus::PENDING);
    SSH::fake(vitoEnvironmentDomainTestCertificate()."\nSuccessfully received certificate");
    app(CheckEnvironmentDomain::class)->check($this->domain->id, $this->server->id);
    $this->assertDatabaseCount('ssls', 1);
    expect($this->domain->fresh()->ssl_id)->toBe($ssl->id)->and($this->domain->fresh()->status)->toBe(HostedDomainStatus::ACTIVE);
});

test('wildcard activation queues dns01 with environment ownership and completes into a vhost', function () {
    $provider = DNSProvider::factory()->create(['project_id' => $this->server->project_id]);
    Domain::factory()->create(['project_id' => $this->server->project_id, 'domain' => 'example.com', 'dns_provider_id' => $provider->id]);
    $this->domain->forceFill(['host' => '*.example.com', 'ssl_method' => SslMethod::LETSENCRYPT])->save();
    ($this->verify)();
    app(CheckEnvironmentDomain::class)->check($this->domain->id, $this->server->id);
    $ssl = $this->domain->fresh()->ssl;
    expect($ssl->environment_id)->toBe($this->environment->id)->and($ssl->server_id)->toBe($this->server->id)->and($ssl->is_wildcard)->toBeTrue();
    expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::UPDATING)->and(app(SyncComponentVhost::class)->generate($this->component))->toBe('');
    Queue::assertPushed(CreateLetsEncryptWildcardSslJob::class);
    $ssl->update(['status' => SslStatus::CREATED, 'certificate_path' => '/etc/ssl/wildcard.pem', 'pk_path' => '/etc/ssl/wildcard.key', 'expires_at' => now()->addDays(60)]);
    app(ActivateEnvironmentDomain::class)->complete($ssl);
    expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::ACTIVE)->and(app(SyncComponentVhost::class)->generate($this->component))->toContain('*.example.com');
});

test('issued certificates remain retryable while the target is temporarily unavailable', function (string $target) {
    $ssl = ($this->certificate)(['is_wildcard' => true, 'domains' => ['*.example.com']]);
    $this->domain->forceFill(['ssl_id' => $ssl->id, 'ssl_method' => SslMethod::LETSENCRYPT, 'status' => HostedDomainStatus::UPDATING])->save();
    if ($target === 'component') {
        $this->component->forceFill(['status' => ComponentStatus::PENDING])->save();
    } else {
        $this->server->update(['status' => ServerStatus::INSTALLING]);
    }
    app(ActivateEnvironmentDomain::class)->complete($ssl);
    expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::PENDING)->and($this->domain->fresh()->error)->toContain('will retry');
    $this->component->forceFill(['status' => ComponentStatus::READY])->save();
    $this->server->update(['status' => ServerStatus::READY]);
    ($this->verify)();
    $this->artisan('domains:check-pending')->assertSuccessful();
    Queue::assertPushed(CheckEnvironmentDomainJob::class);
    app(CheckEnvironmentDomain::class)->check($this->domain->id, $this->server->id);
    expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::ACTIVE)->and($this->domain->fresh()->ssl_id)->toBe($ssl->id);
    Queue::assertNotPushed(CreateLetsEncryptWildcardSslJob::class);
    SSH::assertNotExecutedContains('certbot');
})->with(['component', 'server']);

test('wildcard renewal failures keep valid certificates and active routes', function () {
    $ssl = ($this->certificate)(['is_wildcard' => true]);
    $this->domain->forceFill(['ssl_id' => $ssl->id, 'ssl_method' => SslMethod::LETSENCRYPT, 'status' => HostedDomainStatus::ACTIVE, 'force_ssl' => true])->save();
    (new CreateLetsEncryptWildcardSslJob($this->server, $ssl, true))->failed(new Exception('provider failure'));
    expect($ssl->fresh()->status)->toBe(SslStatus::CREATED)->and($this->domain->fresh()->status)->toBe(HostedDomainStatus::ACTIVE)
        ->and($this->domain->fresh()->error)->toContain('renewal failed');
    expect(app(SyncComponentVhost::class)->generate($this->component->fresh()))->toContain('app.example.com')->toContain('return 301 https://');
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('initial wildcard failure remains retryable without clobbering unrelated activation', function () {
    $ssl = ($this->certificate)(['status' => SslStatus::CREATING, 'is_wildcard' => true]);
    $this->domain->forceFill(['ssl_id' => $ssl->id, 'ssl_method' => SslMethod::LETSENCRYPT, 'status' => HostedDomainStatus::UPDATING])->save();
    (new CreateLetsEncryptWildcardSslJob($this->server, $ssl))->failed(new Exception('provider failure'));
    expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::PENDING)->and($ssl->fresh()->status)->toBe(SslStatus::FAILED);
});

test('environment certificates are reused and deleted only after their final domain', function () {
    $ssl = ($this->certificate)();
    $this->domain->forceFill(['ssl_method' => SslMethod::LETSENCRYPT])->save();
    app(ActivateEnvironmentDomain::class)->activate($this->domain);
    $second = EnvironmentDomain::factory()->for($this->component)->create(['host' => 'www.example.com', 'ssl_method' => SslMethod::LETSENCRYPT]);
    app(ActivateEnvironmentDomain::class)->activate($second);
    expect($this->domain->ssl_id)->toBe($ssl->id)->and($second->ssl_id)->toBe($ssl->id);
    app(DeleteEnvironmentDomain::class)->delete($this->domain);
    $this->assertDatabaseHas('ssls', ['id' => $ssl->id]);
    Queue::assertNotPushed(DeleteServerSslJob::class);
    app(DeleteEnvironmentDomain::class)->delete($second);
    Queue::assertPushed(DeleteServerSslJob::class);
    (new DeleteServerSslJob($this->server, $ssl))->handle();
    $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
    SSH::assertExecutedContains('certbot delete');
});

test('server certificates cannot be deleted while referenced and survive domain removal', function () {
    $ssl = ($this->certificate)(['environment_id' => null, 'type' => 'custom']);
    $this->domain->forceFill(['ssl_method' => SslMethod::CUSTOM, 'ssl_id' => $ssl->id])->save();
    expect(fn () => app(DeleteSsl::class)->delete($ssl))->toThrow(ValidationException::class);
    app(DeleteEnvironmentDomain::class)->delete($this->domain);
    $this->assertDatabaseHas('ssls', ['id' => $ssl->id]);
    SSH::assertNotExecutedContains('certbot delete');
});

test('changing hosts resets verification and removes unused environment certificates', function () {
    $ssl = ($this->certificate)();
    $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE, 'ssl_method' => SslMethod::LETSENCRYPT, 'ssl_id' => $ssl->id])->save();
    app(UpdateEnvironmentDomain::class)->update($this->domain, [
        'host' => 'new.example.com', 'type' => 'alias', 'component_id' => $this->component->id, 'ssl_method' => 'none', 'force_ssl' => false,
    ]);
    $this->assertDatabaseHas('environment_domains', ['id' => $this->domain->id, 'status' => 'pending', 'ssl_id' => null]);
    Queue::assertPushed(DeleteServerSslJob::class);
    (new DeleteServerSslJob($this->server, $ssl))->handle();
    $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
    Queue::assertPushed(CheckEnvironmentDomainJob::class);
});

test('certificate cleanup failures cannot restore removed routing and are reconciled', function () {
    $ssl = ($this->certificate)();
    $this->domain->forceFill(['ssl_id' => $ssl->id, 'ssl_method' => SslMethod::LETSENCRYPT, 'status' => HostedDomainStatus::ACTIVE])->save();
    app(DeleteEnvironmentDomain::class)->delete($this->domain);
    SSH::fake()->execWillFail();
    $job = (new DeleteServerSslJob($this->server, $ssl))->withFakeQueueInteractions();
    $job->handle();
    $job->assertFailedWith(SSHCommandError::class);
    $job->failed(new Exception('cleanup failed'));
    $this->assertDatabaseMissing('environment_domains', ['id' => $this->domain->id]);
    $this->assertDatabaseHas('ssls', ['id' => $ssl->id, 'environment_id' => $this->environment->id]);
    expect(app(SyncComponentVhost::class)->generate($this->component))->toBe('');
    Queue::fake();
    $this->artisan('domains:check-pending')->assertSuccessful();
    Queue::assertPushed(DeleteServerSslJob::class);
    SSH::fake();
    (new DeleteServerSslJob($this->server, $ssl))->handle();
    $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
});

test('certificate deletion jobs protect certificates attached after they were queued', function (bool $owned) {
    $ssl = ($this->certificate)(['environment_id' => $owned ? $this->environment->id : null, 'status' => SslStatus::DELETING]);
    $job = new DeleteServerSslJob($this->server, $ssl);
    $this->domain->forceFill(['ssl_id' => $ssl->id])->save();
    $job->handle();
    $job->failed(new Exception('stale failure'));
    $this->assertDatabaseHas('ssls', ['id' => $ssl->id, 'status' => 'created']);
    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with([false, true]);

test('certificate deletion shares the domain attachment server lock', function () {
    $ssl = ($this->certificate)(['environment_id' => null]);
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(10, Mockery::type(Closure::class))->andThrow(new LockTimeoutException);
    Cache::shouldReceive('lock')->once()->with('unique-queue:server-'.$this->server->id, 600)->andReturn($lock);
    expect(fn () => app(DeleteSsl::class)->delete($ssl))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('ssls', ['id' => $ssl->id, 'status' => 'created']);
    Queue::assertNothingPushed();
});

test('teardown cleans orphaned certificates even after the last component was removed', function (bool $application) {
    Bus::fake();
    $ssl = ($this->certificate)();
    $this->component->delete();
    if ($application) {
        app(DeleteApplication::class)->delete($this->application, ['name' => $this->application->name]);
        Bus::assertChained([DeleteEnvironmentDomainsJob::class, DeleteEnvironmentJob::class, DeleteApplicationJob::class]);
    } else {
        app(DeleteEnvironment::class)->delete($this->environment, ['name' => $this->environment->name]);
        Bus::assertChained([DeleteEnvironmentDomainsJob::class, DeleteEnvironmentJob::class]);
    }
    expect(fn () => app(DeleteEnvironment::class)->handle($this->environment->id))->toThrow(RuntimeException::class);
    app(DeleteEnvironmentDomain::class)->removeForServer($this->environment->id, $this->server->id);
    app(DeleteEnvironment::class)->handle($this->environment->id);
    $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
    $this->assertDatabaseMissing('environments', ['id' => $this->environment->id]);
})->with([false, true]);

test('environment teardown removes domains before components and is retry safe', function () {
    $ssl = ($this->certificate)();
    $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE, 'ssl_method' => SslMethod::LETSENCRYPT, 'ssl_id' => $ssl->id])->save();
    EnvironmentDomain::factory()->for($this->component)->create(['type' => HostedDomainType::REDIRECT, 'redirect_to_domain_id' => $this->domain->id]);
    $jobs = app(DeleteEnvironment::class)->jobs($this->environment);
    expect($jobs[0])->toBeInstanceOf(DeleteEnvironmentDomainsJob::class)->and($jobs[1])->toBeInstanceOf(DeleteComponentJob::class);
    $jobs[0]->handle();
    $jobs[0]->handle();
    $this->assertDatabaseCount('environment_domains', 0);
    $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
    $this->assertDatabaseHas('components', ['id' => $this->component->id]);
    foreach (array_slice($jobs, 1) as $job) {
        $job->handle();
    }
    $this->assertDatabaseMissing('environments', ['id' => $this->environment->id]);
});

test('application teardown also chains domain cleanup first', function () {
    Bus::fake();
    app(DeleteApplication::class)->delete($this->application, ['name' => $this->application->name]);
    Bus::assertChained([DeleteEnvironmentDomainsJob::class, DeleteComponentJob::class, DeleteEnvironmentJob::class, DeleteApplicationJob::class]);
});

test('server purge cascades environment domains and owned certificates without remote calls', function () {
    $ssl = ($this->certificate)();
    $this->domain->forceFill(['ssl_id' => $ssl->id])->save();
    $this->server->delete();
    $this->assertDatabaseMissing('environment_domains', ['id' => $this->domain->id]);
    $this->assertDatabaseMissing('ssls', ['id' => $ssl->id]);
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('environment certificates are excluded from server lists and owner guards still apply', function () {
    $ssl = ($this->certificate)();
    $this->actingAs($this->user)->get(route('server-ssls', $this->server))->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('ssls.data', 0));
    expect(Ssl::activeServerLevel($this->server->id)->whereKey($ssl->id)->exists())->toBeFalse();
    expect($this->user->can('delete', [$ssl, $this->server]))->toBeFalse();
    expect(fn () => $ssl->update(['site_id' => $this->site->id]))->toThrow(LogicException::class);
});

test('pending checks renewal and expiry commands include environment certificates', function () {
    $ssl = ($this->certificate)(['expires_at' => now()->addDays(10)]);
    $this->domain->forceFill(['ssl_id' => $ssl->id, 'ssl_method' => SslMethod::LETSENCRYPT])->save();
    $this->artisan('domains:check-pending')->assertSuccessful();
    Queue::assertPushed(CheckEnvironmentDomainJob::class);
    $this->artisan('ssl:renew-wildcards')->assertSuccessful();
    Queue::assertPushed(RenewEnvironmentSslJob::class);
    $this->artisan('ssl:check-expiry')->assertSuccessful();
    Queue::assertPushed(CheckSslExpiryJob::class);
    SSH::fake(vitoEnvironmentDomainTestCertificate()."\nSuccessfully received certificate");
    (new RenewEnvironmentSslJob($ssl->id, $this->server->id))->handle();
    expect($ssl->fresh()->expires_at->isAfter(now()->addDays(80)))->toBeTrue();
    expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::ACTIVE);
});

test('stale verification jobs do not provision after teardown deletion or retargeting', function (string $state) {
    match ($state) {
        'deleted' => $this->domain->delete(),
        'environment' => $this->environment->forceFill(['status' => EnvironmentStatus::DELETING])->save(),
        'application' => $this->application->forceFill(['status' => ApplicationStatus::DELETING])->save(),
        'active' => $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE])->save(),
        'retargeted' => $this->component->update(['server_id' => Server::factory()->create()->id]),
    };
    (new CheckEnvironmentDomainJob($this->domain->id, $this->server->id))->handle();
    expect(SSH::getExecutedCommands())->toBeEmpty();
    Http::assertNothingSent();
})->with(['deleted', 'environment', 'application', 'active', 'retargeted']);

test('stale verification failure callbacks do not reset active retargeted or tearing down domains', function (string $state) {
    match ($state) {
        'active' => $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE])->save(),
        'updating' => $this->domain->forceFill(['status' => HostedDomainStatus::UPDATING])->save(),
        'retargeted' => $this->component->update(['server_id' => Server::factory()->create()->id]),
        'environment' => $this->environment->forceFill(['status' => EnvironmentStatus::DELETING])->save(),
        'application' => $this->application->forceFill(['status' => ApplicationStatus::DELETING])->save(),
    };
    $status = $this->domain->fresh()->status;
    (new CheckEnvironmentDomainJob($this->domain->id, $this->server->id))->failed(new Exception('old failure'));
    expect($this->domain->fresh()->status)->toBe($status)->and($this->domain->fresh()->error)->toBeNull();
    Event::assertNotDispatched(SocketEvent::class);
})->with(['active', 'updating', 'retargeted', 'environment', 'application']);

test('certificate completion rejects invalid certificates and skips tearing down environments', function () {
    $ssl = ($this->certificate)(['domains' => ['other.example.com']]);
    $this->domain->forceFill(['ssl_id' => $ssl->id, 'ssl_method' => SslMethod::LETSENCRYPT, 'status' => HostedDomainStatus::UPDATING])->save();
    app(ActivateEnvironmentDomain::class)->complete($ssl);
    expect($this->domain->fresh()->status)->toBe(HostedDomainStatus::PENDING)->and($this->domain->fresh()->error)->not->toBeNull();
    $this->environment->forceFill(['status' => EnvironmentStatus::DELETING])->save();
    SSH::fake();
    app(ActivateEnvironmentDomain::class)->complete($ssl);
    app(RenewEnvironmentSsl::class)->renew($ssl->id);
    expect(SSH::getExecutedCommands())->toBeEmpty();
});

test('domain and certificate job failures expose safe errors and tolerate deleted rows', function () {
    $ssl = ($this->certificate)();
    $this->domain->forceFill(['ssl_id' => $ssl->id])->save();
    (new CheckEnvironmentDomainJob($this->domain->id, $this->server->id))->failed(new Exception('secret-provider-response'));
    expect($this->domain->fresh()->error)->not->toContain('secret-provider-response');
    (new RenewEnvironmentSslJob($ssl->id, $this->server->id))->failed(new Exception('secret-provider-response'));
    expect($this->domain->fresh()->error)->toContain('renewal failed')->not->toContain('secret-provider-response');
    (new DeleteEnvironmentDomainsJob($this->environment->id, $this->server->id))->failed(new Exception('secret-provider-response'));
    expect($this->environment->fresh()->status)->toBe(EnvironmentStatus::DELETION_FAILED);
    $this->domain->delete();
    (new CheckEnvironmentDomainJob($this->domain->id, $this->server->id))->failed(new Exception);
    $ssl->delete();
    (new RenewEnvironmentSslJob($ssl->id, $this->server->id))->handle();
    (new RenewEnvironmentSslJob($ssl->id, $this->server->id))->failed(new Exception);
});

test('vhost errors roll back domain mutations and do not enqueue verification', function () {
    SSH::execWillFail();
    expect(fn () => app(UpdateEnvironmentDomain::class)->update($this->domain, [
        'host' => 'new.example.com', 'type' => 'alias', 'component_id' => $this->component->id, 'ssl_method' => 'none', 'force_ssl' => false,
    ]))->toThrow(SSHCommandError::class);
    $this->assertDatabaseHas('environment_domains', ['id' => $this->domain->id, 'host' => 'app.example.com']);
    Queue::assertNothingPushed();
});

test('failed retargeting restores the original route after rolling back the domain', function () {
    $server = Server::factory()->create(['project_id' => $this->server->project_id]);
    $server->services()->create(['name' => 'nginx', 'type' => 'webserver', 'version' => 'latest', 'status' => 'ready']);
    $component = Component::factory()->for($server)->for($this->environment)->create(['status' => ComponentStatus::READY]);
    ComponentCapability::factory()->for($component)->create(['status' => ComponentStatus::READY, 'config' => ['port' => 10001]]);
    $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE])->save();
    $sync = Mockery::mock(SyncComponentVhost::class)->makePartial();
    $sync->shouldReceive('sync')->once()->ordered()->withArgs(fn (Component $target) => $target->id === $this->component->id)->andReturnNull();
    $sync->shouldReceive('sync')->once()->ordered()->withArgs(fn (Component $target) => $target->id === $component->id)->andThrow(new SSHCommandError);
    $sync->shouldReceive('sync')->once()->ordered()->withArgs(function (Component $target) use ($sync): bool {
        expect($sync->generate($target))->toContain('app.example.com');

        return $target->id === $this->component->id;
    })->andReturnNull();
    app()->instance(SyncComponentVhost::class, $sync);
    expect(fn () => app(UpdateEnvironmentDomain::class)->update($this->domain, [
        'host' => 'app.example.com', 'type' => 'alias', 'component_id' => $component->id, 'ssl_method' => 'none', 'force_ssl' => false,
    ]))->toThrow(SSHCommandError::class);
    $this->assertDatabaseHas('environment_domains', ['id' => $this->domain->id, 'status' => 'active', 'component_id' => $this->component->id]);
    Queue::assertNothingPushed();
});

test('retargeting domains resynchronizes both servers and resets verification', function () {
    $server = Server::factory()->create(['project_id' => $this->server->project_id]);
    $server->services()->create(['name' => 'nginx', 'type' => 'webserver', 'version' => 'latest', 'status' => 'ready']);
    $component = Component::factory()->for($server)->for($this->environment)->create(['status' => ComponentStatus::READY]);
    ComponentCapability::factory()->for($component)->create(['status' => ComponentStatus::READY, 'config' => ['port' => 10001]]);
    $this->domain->forceFill(['status' => HostedDomainStatus::ACTIVE])->save();
    app(UpdateEnvironmentDomain::class)->update($this->domain, [
        'host' => 'app.example.com', 'type' => 'alias', 'component_id' => $component->id, 'ssl_method' => 'none', 'force_ssl' => false,
    ]);
    $this->assertDatabaseHas('environment_domains', ['id' => $this->domain->id, 'status' => 'pending', 'component_id' => $component->id]);
    SSH::assertExecutedContains('/sites-available/component-'.$this->component->id);
    SSH::assertExecutedContains('/sites-available/component-'.$component->id);
});
