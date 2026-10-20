<?php

use App\Actions\Component\DeleteComponent;
use App\Actions\Component\SyncComponentVhost;
use App\Actions\Component\UpdateComponentCapabilities;
use App\Actions\EnvironmentDomain\CreateEnvironmentDomain;
use App\Actions\EnvironmentDomain\DeleteEnvironmentDomain;
use App\Actions\EnvironmentDomain\GetEnvironmentDomainOptions;
use App\Actions\EnvironmentDomain\UpdateEnvironmentDomain;
use App\Actions\HostedDomain\AssertHostAvailable;
use App\Actions\HostedDomain\CreateHostedDomain;
use App\Actions\HostedDomain\UpdateHostedDomain;
use App\Actions\Site\CreateSite;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentStatus;
use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\ServerStatus;
use App\Enums\SslStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Jobs\EnvironmentDomain\CheckEnvironmentDomainJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\DNSProvider;
use App\Models\Domain;
use App\Models\Environment;
use App\Models\EnvironmentDomain;
use App\Models\HostedDomain;
use App\Models\Project;
use App\Models\Server;
use App\Models\Ssl;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->component = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    ComponentCapability::factory()->for($this->component)->create(['capability' => 'web', 'status' => ComponentStatus::READY, 'config' => ['port' => 10000]]);
    $this->input = ['host' => 'app.example.com', 'type' => 'alias', 'component_id' => $this->component->id, 'ssl_method' => 'none', 'force_ssl' => false];
});

test('environment domains start pending without serving traffic or creating certificates', function () {
    $domain = app(CreateEnvironmentDomain::class)->create($this->environment, array_replace($this->input, [
        'host' => ' APP.Example.com ', 'status' => 'active', 'environment_id' => 999, 'ssl_id' => 999,
    ]));

    $this->assertDatabaseHas('environment_domains', [
        'id' => $domain->id, 'host' => 'app.example.com', 'environment_id' => $this->environment->id,
        'component_id' => $this->component->id, 'status' => 'pending', 'ssl_id' => null,
    ]);
    $this->assertDatabaseCount('ssls', 0);
    expect(app(SyncComponentVhost::class)->generate($this->component))->toBe('');
    SSH::assertNotExecutedContains('certbot');
    Queue::assertPushed(CheckEnvironmentDomainJob::class);
    Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event) => $event->data->type === 'environment-domain.updated');
});

test('environment domains reject malformed input before side effects', function (string $field, mixed $value) {
    expect(fn () => app(CreateEnvironmentDomain::class)->create($this->environment, array_replace($this->input, [$field => $value])))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('environment_domains', 0);
    Queue::assertNothingPushed();
    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with([
    ['host', ''], ['host', []], ['host', 'https://example.com'], ['host', 'example.com;id'], ['host', "example.com\nother"],
    ['host', '-invalid.example.com'], ['host', 'foo.*.example.com'], ['type', 'primary'], ['type', 'other'],
    ['component_id', null], ['component_id', 999999], ['ssl_method', 'other'], ['ssl_method', 'custom'], ['force_ssl', 'yes'],
]);

test('environment domain targets must be ready web components of the same environment and project', function (string $kind) {
    match ($kind) {
        'component' => $this->component->forceFill(['status' => ComponentStatus::PENDING])->save(),
        'server' => $this->server->update(['status' => ServerStatus::INSTALLING]),
        'foreign-server' => $this->server->update(['project_id' => Project::factory()->create()->id]),
        'foreign-environment' => $this->component->update(['environment_id' => Environment::factory()->create()->id]),
        'lb-pending' => $this->component->forceFill(['type' => ComponentType::LOAD_BALANCER, 'status' => ComponentStatus::PENDING])->save(),
        'no-web' => $this->component->capabilities()->delete(),
        'web-pending' => $this->component->capabilities()->update(['status' => ComponentStatus::PENDING]),
        'no-webserver' => $this->server->services()->where('type', 'webserver')->delete(),
        'environment' => $this->environment->forceFill(['status' => EnvironmentStatus::DELETING])->save(),
        'application' => $this->application->forceFill(['status' => ApplicationStatus::DELETING])->save(),
    };
    expect(fn () => app(CreateEnvironmentDomain::class)->create($this->environment, $this->input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('environment_domains', 0);
})->with(['component', 'server', 'foreign-server', 'foreign-environment', 'lb-pending', 'no-web', 'web-pending', 'no-webserver', 'environment', 'application']);

test('domain collisions include sites aliases hosted domains and other environments on the target server', function (string $kind) {
    match ($kind) {
        'site' => $this->site->update(['domain' => 'APP.EXAMPLE.COM']),
        'alias' => $this->site->update(['aliases' => ['app.example.com']]),
        'hosted' => HostedDomain::factory()->create(['site_id' => $this->site->id, 'domain' => 'app.example.com']),
        'environment' => EnvironmentDomain::factory()->for(Component::factory()->for($this->server))->create(['host' => 'app.example.com']),
        'wildcard' => EnvironmentDomain::factory()->for(Component::factory()->for($this->server))->create(['host' => '*.example.com']),
    };
    expect(fn () => app(CreateEnvironmentDomain::class)->create($this->environment, $this->input))->toThrow(ValidationException::class);
    Queue::assertNothingPushed();
})->with(['site', 'alias', 'hosted', 'environment', 'wildcard']);

test('same hosts on other servers are allowed and site checks also see environment domains', function () {
    EnvironmentDomain::factory()->create(['host' => $this->input['host']]);
    $domain = app(CreateEnvironmentDomain::class)->create($this->environment, $this->input);
    expect(fn () => app(AssertHostAvailable::class)->assert($this->server, $domain->host, 'domain'))->toThrow(ValidationException::class);
    app(AssertHostAvailable::class)->assert($this->server, $domain->host, 'host', $domain);
    expect(app(UpdateEnvironmentDomain::class)->update($domain, $this->input)->id)->toBe($domain->id);
});

test('wildcard domains require the matching project domain and project dns provider', function (string $kind) {
    if ($kind !== 'missing') {
        $provider = DNSProvider::factory()->create(['project_id' => $kind === 'foreign-provider' ? Project::factory()->create()->id : $this->server->project_id]);
        Domain::factory()->create([
            'domain' => 'example.com', 'project_id' => $kind === 'foreign-domain' ? Project::factory()->create()->id : $this->server->project_id,
            'dns_provider_id' => $provider->id,
        ]);
        if ($kind === 'no-provider') {
            $provider->delete();
        }
    }
    $create = fn () => app(CreateEnvironmentDomain::class)->create($this->environment, array_replace($this->input, ['host' => '*.example.com']));
    if ($kind === 'valid') {
        expect($create()->host)->toBe('*.example.com');
    } else {
        expect($create)->toThrow(ValidationException::class);
    }
})->with(['missing', 'no-provider', 'foreign-domain', 'foreign-provider', 'valid']);

test('redirects inherit the alias component and protect referenced aliases from deletion or retargeting', function () {
    $alias = app(CreateEnvironmentDomain::class)->create($this->environment, $this->input);
    $redirect = app(CreateEnvironmentDomain::class)->create($this->environment, array_replace($this->input, [
        'host' => 'redirect.example.com', 'type' => 'redirect', 'redirect_to_domain_id' => $alias->id, 'component_id' => 999999,
    ]));
    expect($redirect->component_id)->toBe($alias->component_id);
    expect(fn () => app(DeleteEnvironmentDomain::class)->delete($alias))->toThrow(ValidationException::class);
    expect(fn () => app(UpdateEnvironmentDomain::class)->update($alias, array_replace($this->input, [
        'type' => 'redirect', 'redirect_to_domain_id' => $redirect->id,
    ])))->toThrow(ValidationException::class);
    app(DeleteEnvironmentDomain::class)->delete($redirect);
    app(DeleteEnvironmentDomain::class)->delete($alias);
    $this->assertDatabaseCount('environment_domains', 0);
});

test('redirect targets cannot be foreign wildcard redirects or the domain itself', function (string $kind) {
    $target = EnvironmentDomain::factory()->for($this->component)->create();
    $domain = EnvironmentDomain::factory()->for($this->component)->create();
    match ($kind) {
        'foreign' => $target->forceFill(['environment_id' => Environment::factory()->create()->id])->save(),
        'wildcard' => $target->update(['host' => '*.example.com']),
        'redirect' => $target->update(['type' => HostedDomainType::REDIRECT]),
        'self' => $target = $domain,
    };
    expect(fn () => app(UpdateEnvironmentDomain::class)->update($domain, array_replace($this->input, [
        'type' => 'redirect', 'redirect_to_domain_id' => $target->id,
    ])))->toThrow(ValidationException::class);
})->with(['foreign', 'wildcard', 'redirect', 'self']);

test('custom certificates must be active server certificates on the target server', function (string $kind) {
    $ssl = Ssl::factory()->create([
        'site_id' => $kind === 'site' ? $this->site->id : null,
        'server_id' => $kind === 'foreign' ? Server::factory()->create()->id : $this->server->id,
        'environment_id' => $kind === 'environment' ? $this->environment->id : null,
        'type' => 'custom', 'status' => $kind === 'failed' ? SslStatus::FAILED : SslStatus::CREATED,
        'certificate_path' => $kind === 'uninstalled' ? null : '/etc/ssl/test.pem',
        'domains' => $kind === 'uncovered' ? ['other.example.com'] : ['app.example.com'],
        'expires_at' => $kind === 'expired' ? now()->subDay() : now()->addDay(),
    ]);
    $create = fn () => app(CreateEnvironmentDomain::class)->create($this->environment, array_replace($this->input, ['ssl_method' => 'custom', 'ssl_id' => $ssl->id]));
    if ($kind === 'valid') {
        expect($create()->ssl_id)->toBe($ssl->id);
    } else {
        expect($create)->toThrow(ValidationException::class);
    }
})->with(['site', 'foreign', 'environment', 'failed', 'uninstalled', 'uncovered', 'expired', 'valid']);

test('targeted components and web capabilities cannot be removed', function () {
    EnvironmentDomain::factory()->for($this->component)->create();
    expect(fn () => app(DeleteComponent::class)->delete($this->component, []))->toThrow(ValidationException::class);
    expect(fn () => app(UpdateComponentCapabilities::class)->update($this->component, ['capabilities' => []]))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('component_capabilities', ['component_id' => $this->component->id, 'capability' => 'web', 'status' => 'ready']);
    Queue::assertNothingPushed();
});

test('busy domain servers return retryable validation errors without mutations', function () {
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(10, Mockery::type(Closure::class))->andThrow(new LockTimeoutException);
    Cache::shouldReceive('lock')->once()->with('unique-queue:server-'.$this->server->id, 600)->andReturn($lock);
    expect(fn () => app(CreateEnvironmentDomain::class)->create($this->environment, $this->input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('environment_domains', 0);
});

test('site hosted and environment writers share the server host reservation lock', function (string $writer) {
    $hosted = HostedDomain::factory()->create(['site_id' => $this->site->id, 'status' => HostedDomainStatus::ACTIVE]);
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->with(10, Mockery::type(Closure::class))->andThrow(new LockTimeoutException);
    if ($writer === 'environment') {
        $serverLock = Mockery::mock(Lock::class);
        $serverLock->shouldReceive('block')->once()->andReturnUsing(fn ($seconds, $callback) => $callback());
        Cache::shouldReceive('lock')->once()->with('unique-queue:server-'.$this->server->id, 600)->andReturn($serverLock);
    }
    Cache::shouldReceive('lock')->once()->with('host-reservations:server-'.$this->server->id, 600)->andReturn($lock);
    $write = fn () => match ($writer) {
        'site' => app(CreateSite::class)->create($this->server, [], $this->user),
        'hosted-create' => app(CreateHostedDomain::class)->create($this->site, []),
        'hosted-update' => app(UpdateHostedDomain::class)->update($hosted, $this->site, []),
        'environment' => app(CreateEnvironmentDomain::class)->create($this->environment, $this->input),
    };
    expect($write)->toThrow(ValidationException::class, 'Another domain operation');
    $this->assertDatabaseCount('environment_domains', 0);
    $this->assertDatabaseHas('hosted_domains', ['id' => $hosted->id, 'domain' => $hosted->domain]);
    Queue::assertNothingPushed();
    expect(SSH::getExecutedCommands())->toBeEmpty();
})->with(['site', 'hosted-create', 'hosted-update', 'environment']);

test('certificate options exclude expired unknown and environment certificates', function () {
    $attributes = ['site_id' => null, 'server_id' => $this->server->id, 'domains' => ['app.example.com']];
    $valid = Ssl::factory()->create($attributes + ['expires_at' => now()->addDay()]);
    Ssl::factory()->create($attributes + ['expires_at' => now()->subDay()]);
    Ssl::factory()->create($attributes + ['expires_at' => null]);
    Ssl::factory()->create($attributes + ['expires_at' => now()->addDay(), 'environment_id' => $this->environment->id]);
    $server = Server::factory()->create(['project_id' => $this->server->project_id]);
    $component = Component::factory()->for($this->environment)->for($server)->create(['status' => ComponentStatus::READY]);
    ComponentCapability::factory()->for($component)->create(['capability' => 'web', 'status' => ComponentStatus::READY]);
    $other = Ssl::factory()->create(array_replace($attributes, ['server_id' => $server->id, 'expires_at' => now()->addDay()]));
    Ssl::factory()->create(array_replace($attributes, ['server_id' => Server::factory()->create()->id, 'expires_at' => now()->addDay()]));
    Ssl::factory()->create($attributes + ['expires_at' => now()->addDay(), 'status' => SslStatus::FAILED]);
    Ssl::factory()->create($attributes + ['expires_at' => now()->addDay(), 'certificate_path' => null]);
    Ssl::factory()->create($attributes + ['expires_at' => now()->addDay(), 'pk_path' => null]);
    Ssl::factory()->create($attributes + ['expires_at' => now()->addDay(), 'type' => 'csr']);
    Ssl::factory()->create(array_replace($attributes, ['expires_at' => now()->addDay(), 'domains' => []]));
    $connection = (new Ssl)->getConnection();
    $connection->enableQueryLog();
    try {
        $options = app(GetEnvironmentDomainOptions::class)->get($this->environment);
        expect(collect($connection->getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'from "ssls"')))->toHaveCount(1);
    } finally {
        $connection->disableQueryLog();
    }
    expect(array_column($options['certificates'], 'id'))->toBe([$valid->id, $other->id]);
});

test('domain endpoints list scoped options create edit verify and delete', function () {
    $this->actingAs($this->user);
    $parents = [$this->application, $this->environment];
    $this->post(route('environments.domains.store', $parents), array_replace($this->input, [
        'component_id' => (string) $this->component->id, 'redirect_to_domain_id' => '', 'ssl_id' => '',
    ]))->assertSessionDoesntHaveErrors();
    $domain = $this->environment->domains()->sole();
    EnvironmentDomain::factory()->create();
    $this->get(route('environments.domains', $parents))->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('applications/environments/domains')->has('domains.data', 1)->has('domainOptions.components', 1)
        ->where('domains.data.0.domain.id', $domain->id)->where('domains.data.0.domain.ssl_method', 'disabled')
        ->where('domains.data.0._type_enum_color', 'default')
        ->where('domainOptions.components.0.server_ip', $this->server->ip)->where('canUpdate', true)
        ->missing('domains.data.0.domain.certificate_path'));
    $this->put(route('environments.domains.update', [...$parents, $domain]), array_replace($this->input, ['host' => 'new.example.com']))->assertSessionDoesntHaveErrors();
    $this->post(route('environments.domains.verify', [...$parents, $domain]))->assertSessionDoesntHaveErrors();
    $this->assertDatabaseHas('environment_domains', ['id' => $domain->id, 'host' => 'new.example.com', 'status' => 'pending']);
    $this->delete(route('environments.domains.destroy', [...$parents, $domain]))->assertSessionDoesntHaveErrors();
    $this->assertDatabaseMissing('environment_domains', ['id' => $domain->id]);
});

test('domain endpoints enforce authentication project roles and parent scoping', function (string $access) {
    $domain = EnvironmentDomain::factory()->for($this->component)->create();
    if ($access !== 'guest') {
        $this->actingAs($this->user);
    }
    if ($access === 'reader') {
        $this->user->currentProject->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    } elseif ($access === 'foreign') {
        $this->application->update(['project_id' => Project::factory()->create()->id]);
    }
    $parents = [$this->application, $this->environment];
    if ($access === 'wrong-parent') {
        $parents[1] = Environment::factory()->for($this->application)->create();
    }
    $status = match ($access) {
        'guest' => 401, 'wrong-parent' => 404, default => 403
    };
    if ($access === 'reader') {
        $this->get(route('environments.domains', $parents))->assertSuccessful()->assertInertia(fn (AssertableInertia $page) => $page->where('canUpdate', false));
    } elseif ($access !== 'wrong-parent') {
        $this->getJson(route('environments.domains', $parents))->assertStatus($status);
    }
    if ($access !== 'wrong-parent') {
        $this->postJson(route('environments.domains.store', $parents), $this->input)->assertStatus($status);
    }
    $this->putJson(route('environments.domains.update', [...$parents, $domain]), $this->input)->assertStatus($status);
    $this->postJson(route('environments.domains.verify', [...$parents, $domain]))->assertStatus($status);
    $this->deleteJson(route('environments.domains.destroy', [...$parents, $domain]))->assertStatus($status);
    Queue::assertNothingPushed();
})->with(['guest', 'reader', 'foreign', 'wrong-parent']);

test('invalid domain requests are validation errors and processing domains cannot change', function () {
    $this->actingAs($this->user);
    $parents = [$this->application, $this->environment];
    $this->postJson(route('environments.domains.store', $parents), [])->assertUnprocessable();
    $domain = EnvironmentDomain::factory()->for($this->component)->create(['status' => HostedDomainStatus::UPDATING]);
    $this->putJson(route('environments.domains.update', [...$parents, $domain]), $this->input)->assertUnprocessable();
    $this->postJson(route('environments.domains.verify', [...$parents, $domain]))->assertUnprocessable();
});
