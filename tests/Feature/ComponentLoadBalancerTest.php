<?php

use App\Actions\Component\CreateComponent;
use App\Actions\Component\DeleteComponent;
use App\Actions\Component\InstallComponent;
use App\Actions\Component\SyncComponentRuntime;
use App\Actions\Component\SyncComponentVhost;
use App\Actions\Component\SyncLoadBalancerBackends;
use App\Actions\Component\UpdateLoadBalancerBackends;
use App\Actions\ComponentCapability\InstallWebCapability;
use App\Actions\Environment\DeleteEnvironment;
use App\Actions\EnvironmentDomain\CreateEnvironmentDomain;
use App\Actions\FirewallRule\ManageRule;
use App\Actions\Network\DeleteNetwork;
use App\Actions\Network\RemoveServerFromNetwork;
use App\Actions\Network\ResolveServerAddress;
use App\Actions\PHP\EnsureFpmPool;
use App\Actions\ServerIp\ManageServerIp;
use App\Actions\Service\Manage;
use App\Actions\Service\Uninstall;
use App\Actions\Site\UpdatePort;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentDeploymentStatus;
use App\Enums\EnvironmentStatus;
use App\Enums\FirewallRuleStatus;
use App\Enums\HostedDomainStatus;
use App\Enums\IpAddressStatus;
use App\Enums\LoadBalancerMethod;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Enums\ServiceStatus;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Exceptions\SSHError;
use App\Facades\SSH;
use App\Jobs\Component\SyncLoadBalancerServerJob;
use App\Jobs\FirewallRule\ApplyRulesJob;
use App\Jobs\ServerIp\PersistServerIpsJob;
use App\Jobs\Service\ManageJob;
use App\Jobs\Service\UninstallJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\ComponentCapability;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\EnvironmentDomain;
use App\Models\FirewallRule;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Models\Ssl;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    $this->application = Application::factory()->create(['project_id' => $this->server->project_id, 'runtime' => 'custom']);
    $this->environment = Environment::factory()->for($this->application)->create();
    $this->lb = Component::factory()->for($this->environment)->for($this->server)->create([
        'type' => ComponentType::LOAD_BALANCER, 'status' => ComponentStatus::READY, 'load_balancer_method' => LoadBalancerMethod::ROUND_ROBIN,
    ]);
    $this->target = Component::factory()->for($this->environment)->for($this->server)->create(['status' => ComponentStatus::READY]);
    ComponentCapability::factory()->for($this->target)->create(['capability' => 'web', 'status' => ComponentStatus::READY, 'config' => ['port' => 10000]]);
    $this->input = ['method' => 'round-robin', 'backends' => [
        ['target_component_id' => $this->target->id, 'ip' => null, 'port' => 8080, 'weight' => null, 'backup' => false],
    ]];
});

function placeLoadBalancerTargetOnNetwork(Component $target, Server $loadBalancer): NetworkServer
{
    $server = Server::factory()->create(['project_id' => $loadBalancer->project_id, 'local_ip' => '192.168.90.99']);
    foreach (['webserver' => 'nginx', 'firewall' => 'ufw'] as $type => $name) {
        $server->services()->create(['type' => $type, 'name' => $name, 'version' => 'latest', 'status' => ServiceStatus::READY]);
    }
    $target->update(['server_id' => $server->id]);
    $network = Network::factory()->create(['project_id' => $server->project_id, 'cidr' => '10.40.0.0/24']);
    NetworkServer::factory()->for($network)->for($loadBalancer)->create(['ip' => '10.40.0.1']);

    return NetworkServer::factory()->for($network)->for($server)->create(['ip' => '10.40.0.2']);
}

test('load balancers install only vhosts and never become primary or need runtime services', function () {
    $this->lb->delete();
    $this->target->delete();
    $this->application->update(['runtime' => 'php', 'source_control_id' => null]);
    $this->server->services()->where('type', '!=', 'webserver')->delete();
    $component = app(CreateComponent::class)->create($this->environment->fresh(), [
        'name' => 'Edge', 'server_id' => $this->server->id, 'type' => 'load_balancer', 'user' => 'untrusted', 'capabilities' => [],
    ]);
    app(InstallComponent::class)->install($component->id);
    $this->assertDatabaseHas('components', ['id' => $component->id, 'status' => 'ready', 'user' => $this->server->getSshUser(), 'path' => '', 'deploy_key_id' => null]);
    expect($this->environment->fresh()->primary_component_id)->toBeNull();
    expect($this->environment->fresh()->primary_component_initialized)->toBeFalse();
    SSH::assertExecutedContains('component-'.$component->id);
    SSH::assertNotExecutedContains('mkdir -p --');
    SSH::assertNotExecutedContains('ssh-keygen');
    Http::assertNothingSent();
});

test('load balancer addresses use shared memberships instead of server local ip', function () {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    $resolver = app(ResolveServerAddress::class);
    expect($resolver->between($this->server, $member->server))->toBe('10.40.0.2');
    expect($resolver->between($member->server, $this->server))->toBe('10.40.0.1');
    expect($resolver->between($this->server, $this->server))->toBe('127.0.0.1');
    $member->status = NetworkServerStatus::LEAVING;
    $member->save();
    expect(fn () => $resolver->between($this->server, $member->server))->toThrow(ValidationException::class);
});

test('load balancer backends create private source firewall rules and remove them with their rows', function () {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($member->server);
    $this->assertDatabaseHas('firewall_rules', ['server_id' => $member->server_id, 'source' => '10.40.0.1', 'port' => '8080', 'mask' => '32', 'component_managed' => true]);
    $backend = $this->lb->backends()->sole();
    expect($backend->firewall_rule_id)->not->toBeNull();
    expect($this->user->can('update', $backend->firewallRule))->toBeFalse();
    expect($this->user->can('delete', $backend->firewallRule))->toBeFalse();
    SSH::assertExecutedContains('deny');
    app(UpdateLoadBalancerBackends::class)->update($this->lb, ['method' => 'round-robin', 'backends' => []]);
    app(SyncLoadBalancerBackends::class)->sync($member->server);
    $this->assertDatabaseMissing('firewall_rules', ['id' => $backend->firewall_rule_id]);
    expect(app(SyncComponentVhost::class)->generate($this->target->fresh()))->toBe('');
});

test('load balancer backend validation is atomic and rejects malformed or unsupported input', function (string $field, mixed $value) {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    $id = $this->lb->backends()->sole()->id;
    $input = $this->input;
    data_set($input, $field, $value);
    expect(fn () => app(UpdateLoadBalancerBackends::class)->update($this->lb, $input))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('component_backends', ['id' => $id]);
})->with([
    ['method', 'bad'], ['backends', 'invalid'], ['backends.0.target_component_id', 999999],
    ['backends.0.ip', '127.0.0.1'], ['backends.0.port', 80], ['backends.0.port', 10000], ['backends.0.port', 65536],
    ['backends.0.port', '8080;id'], ['backends.0.weight', 0], ['backends.0.weight', '2;id'], ['backends.0.backup', true],
]);

test('load balancer targets must be ready web components in the same environment with a network', function (string $kind) {
    match ($kind) {
        'foreign' => $this->target->update(['environment_id' => Environment::factory()->create()->id]),
        'no-web' => $this->target->capabilities()->delete(),
        'not-ready' => $this->target->forceFill(['status' => ComponentStatus::PENDING])->save(),
        'self' => $this->input['backends'][0]['target_component_id'] = $this->lb->id,
        'no-network' => $this->target->update(['server_id' => Server::factory()->create(['project_id' => $this->server->project_id])->id]),
        'no-firewall' => $this->server->services()->where('type', 'firewall')->delete(),
    };
    expect(fn () => app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('component_backends', 0);
})->with(['foreign', 'no-web', 'not-ready', 'self', 'no-network', 'no-firewall']);

test('external backends get no managed firewall and ipv6 upstreams are bracketed', function () {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, ['method' => 'least-connections', 'backends' => [
        ['ip' => '2001:db8::2', 'port' => 8080, 'weight' => 3, 'backup' => false],
        ['ip' => '192.0.2.2', 'port' => 8080, 'weight' => null, 'backup' => true],
    ]]);
    EnvironmentDomain::factory()->for($this->lb)->create(['status' => HostedDomainStatus::ACTIVE]);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    $this->assertDatabaseCount('firewall_rules', 0);
    $config = app(SyncComponentVhost::class)->generate($this->lb->fresh());
    expect($config)->toContain('least_conn;', 'server [2001:db8::2]:8080 weight=3 max_fails=3 fail_timeout=10s;', 'server 192.0.2.2:8080 backup', 'X-Forwarded-Host', 'X-Forwarded-For', 'X-Forwarded-Proto');
});

test('backend vhosts bind private addresses and trust only load balancer sources', function (string $webserver) {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    $member->server->webserver()->update(['name' => $webserver]);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    $config = app(SyncComponentVhost::class)->generate($this->target->fresh());
    if ($webserver === 'nginx') {
        expect($config)->toContain('listen 10.40.0.2:8080;', 'set_real_ip_from 10.40.0.1;', 'real_ip_header X-Forwarded-For;', '$realip_remote_addr', 'return 403;', '_scheme;');
    } else {
        expect($config)->toContain('http://:8080', 'bind 10.40.0.2', 'route {', 'not remote_ip 10.40.0.1', 'trusted_proxies 10.40.0.1', 'X-Forwarded-Proto');
    }
    expect($config)->not->toContain($member->server->local_ip);
})->with(['nginx', 'caddy']);

test('caddy load balancers render policy and passive checks and reject ignored settings', function (string $method, string $policy) {
    $this->server->webserver()->update(['name' => 'caddy']);
    $this->input['method'] = $method;
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    EnvironmentDomain::factory()->for($this->lb)->create(['status' => HostedDomainStatus::ACTIVE]);
    $config = app(SyncComponentVhost::class)->generate($this->lb->fresh());
    expect($config)->toContain('lb_policy '.$policy, 'fail_duration 10s', 'max_fails 3', 'unhealthy_status 5xx');
    $this->input['backends'][0]['weight'] = 2;
    expect(fn () => app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input))->toThrow(ValidationException::class);
})->with([['round-robin', 'round_robin'], ['least-connections', 'least_conn'], ['ip-hash', 'ip_hash']]);

test('load balancers are domain targets without creating backend domains', function () {
    $domain = app(CreateEnvironmentDomain::class)->create($this->environment, [
        'host' => 'edge.example.com', 'component_id' => $this->lb->id, 'type' => 'alias', 'ssl_method' => 'none', 'force_ssl' => false,
    ]);
    expect($domain->component_id)->toBe($this->lb->id);
    expect($this->target->domains()->exists())->toBeFalse();
    expect(fn () => app(DeleteComponent::class)->delete($this->lb, []))->toThrow(ValidationException::class);
});

test('network membership removal is blocked on both ends of a managed backend', function (bool $loadBalancer) {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    if ($loadBalancer) {
        $member = NetworkServer::query()->where('network_id', $member->network_id)->where('server_id', $this->server->id)->sole();
    }
    expect(fn () => app(RemoveServerFromNetwork::class)->remove($member))->toThrow(ValidationException::class);
    expect($member->fresh()->status)->toBe(NetworkServerStatus::ACTIVE);
})->with([false, true]);

test('private ip changes queue resyncs and replace trusted addresses and firewall sources', function () {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($member->server);
    $lbMember = NetworkServer::query()->where('network_id', $member->network_id)->where('server_id', $this->server->id)->sole();
    $lbMember->update(['ip' => '10.40.0.9']);
    Queue::assertPushed(SyncLoadBalancerServerJob::class, fn ($job): bool => $job->serverId === $member->server_id);
    app(SyncLoadBalancerBackends::class)->sync($member->server);
    $this->assertDatabaseHas('firewall_rules', ['source' => '10.40.0.9', 'port' => '8080']);
    $this->assertDatabaseMissing('firewall_rules', ['source' => '10.40.0.1']);
    expect(app(SyncComponentVhost::class)->generate($this->target->fresh()))->toContain('set_real_ip_from 10.40.0.9;');
});

test('load balancer deletion removes its vhost without deleting app paths and cleans backend rules', function () {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($member->server);
    app(DeleteComponent::class)->delete($this->lb, []);
    app(DeleteComponent::class)->handle($this->lb->id);
    app(SyncLoadBalancerBackends::class)->sync($member->server);
    $this->assertDatabaseMissing('components', ['id' => $this->lb->id]);
    $this->assertDatabaseCount('component_backends', 0);
    $this->assertDatabaseCount('firewall_rules', 0);
    SSH::assertNotExecutedContains('rm -rf');
});

test('backend rows require exactly one target', function () {
    expect(fn () => $this->lb->backends()->create(['port' => 8080]))->toThrow(LogicException::class);
    expect(fn () => $this->lb->backends()->create(['target_component_id' => $this->target->id, 'ip' => '127.0.0.1', 'port' => 8080]))->toThrow(LogicException::class);
});

test('backend editor is scoped policy protected and whitelists backend fields', function () {
    $url = '/applications/'.$this->application->id.'/environments/'.$this->environment->id.'/components/'.$this->lb->id;
    $panel = route('environments.show', [$this->application, $this->environment, 'component' => $this->lb->id]);
    $this->actingAs($this->user)->put($url.'/backends', $this->input)->assertRedirect()->assertSessionHasNoErrors();
    $this->get($url)->assertRedirect($panel);
    $this->get($panel)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('component.type', 'load_balancer')->where('component.load_balancer_method', 'round-robin')
        ->where('component.backends.0.target_component_id', $this->target->id)->missing('component.backends.0.firewall_rule_id')
        ->where('backendTargets.0.id', $this->target->id)->where('can.updateComponent', true));
    $foreign = Environment::factory()->for($this->application)->create();
    $this->putJson(str_replace('/environments/'.$this->environment->id, '/environments/'.$foreign->id, $url).'/backends', $this->input)->assertNotFound();
    $this->putJson($url.'/backends', ['method' => 'bad', 'backends' => []])->assertUnprocessable();
    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $this->get($panel)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('can.updateComponent', false));
    $this->putJson($url.'/backends', $this->input)->assertForbidden();
});

test('server deletion reconciles surviving ends and removes orphaned firewall rules', function (bool $deleteLoadBalancer) {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($member->server);
    $removed = $deleteLoadBalancer ? $this->server : $member->server;
    $survivor = $deleteLoadBalancer ? $member->server : $this->server;
    $removed->delete();
    Queue::assertPushed(SyncLoadBalancerServerJob::class, fn ($job): bool => $job->serverId === $survivor->id);
    (new SyncLoadBalancerServerJob($survivor->id))->handle();
    (new SyncLoadBalancerServerJob($removed->id))->handle();
    $this->assertDatabaseCount('component_backends', 0);
    $this->assertDatabaseCount('firewall_rules', 0);
    if (! $deleteLoadBalancer) {
        expect($this->lb->fresh()->last_error)->toBeNull();
    }
})->with([false, true]);

test('web removal and target deletion detach backends and preserve unrelated firewall rules', function (bool $removeWeb) {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($member->server);
    $rule = FirewallRule::factory()->for($member->server)->create();
    if ($removeWeb) {
        $capability = $this->target->capabilities()->sole();
        $capability->status = ComponentStatus::DELETING;
        $capability->save();
        app(InstallWebCapability::class)->uninstall($capability, $this->target->fresh());
        $capability->delete();
    } else {
        app(DeleteComponent::class)->delete($this->target, []);
        app(DeleteComponent::class)->handle($this->target->id);
    }
    app(SyncLoadBalancerBackends::class)->sync($member->server);
    $this->assertDatabaseCount('component_backends', 0);
    $this->assertDatabaseCount('firewall_rules', 1);
    $this->assertDatabaseHas('firewall_rules', ['id' => $rule->id]);
})->with([false, true]);

test('firewall failure retains cleanup custody and a retried sync converges', function () {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, ['method' => 'round-robin', 'backends' => []]);
    SSH::fake()->execWillFail();
    expect(fn () => app(SyncLoadBalancerBackends::class)->sync($this->server))->toThrow(SSHError::class);
    $this->assertDatabaseHas('firewall_rules', ['component_managed' => true, 'status' => FirewallRuleStatus::DELETING]);
    (new SyncLoadBalancerServerJob($this->server->id))->failed(new Exception('sensitive-internal-detail'));
    expect($this->lb->fresh()->last_error)->toContain('synchronization failed')->not->toContain('sensitive-internal-detail');
    SSH::fake();
    (new SyncLoadBalancerServerJob($this->server->id))->handle();
    $this->assertDatabaseCount('firewall_rules', 0);
    expect($this->lb->fresh()->last_error)->toBeNull();
});

test('linked private addresses are preferred and required addresses cannot be removed', function () {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    $address = ServerIpAddress::factory()->for($member->server)->create(['ip' => '10.40.0.8']);
    $member->update(['server_ip_address_id' => $address->id]);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    expect(app(ResolveServerAddress::class)->between($this->server, $member->server))->toBe('10.40.0.8');
    $address->update(['ip' => '10.40.0.9']);
    expect(app(ResolveServerAddress::class)->between($this->server, $member->server))->toBe('10.40.0.9');
    Queue::assertPushed(SyncLoadBalancerServerJob::class, fn ($job): bool => $job->serverId === $this->server->id);
    expect(fn () => $address->delete())->toThrow(ValidationException::class);
    expect(fn () => app(DeleteNetwork::class)->delete($member->network))->toThrow(ValidationException::class);
});

test('backend editing is blocked during deployment teardown or app capability updates', function (string $state) {
    match ($state) {
        'deployment' => EnvironmentDeployment::factory()->for($this->environment)->create(['status' => EnvironmentDeploymentStatus::RUNNING]),
        'teardown' => $this->environment->forceFill(['status' => EnvironmentStatus::DELETING])->save(),
        'not-lb' => $this->lb = $this->target,
    };
    expect(fn () => app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input))->toThrow(ValidationException::class);
})->with(['deployment', 'teardown', 'not-lb']);

test('duplicate targets and port collisions are rejected', function (string $kind) {
    $input = $this->input;
    $input['backends'][] = $input['backends'][0];
    if ($kind === 'other-target') {
        $other = Component::factory()->for($this->environment)->for($this->server)->create(['user' => 'other', 'status' => ComponentStatus::READY]);
        ComponentCapability::factory()->for($other)->create(['capability' => 'web', 'status' => ComponentStatus::READY]);
        $input['backends'][1]['target_component_id'] = $other->id;
    } elseif ($kind === 'ip-hash') {
        $input['method'] = 'ip-hash';
        $input['backends'][1] = ['ip' => '192.0.2.1', 'port' => 80, 'backup' => true];
    }
    expect(fn () => app(UpdateLoadBalancerBackends::class)->update($this->lb, $input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('component_backends', 0);
})->with(['duplicate', 'other-target', 'ip-hash']);

test('empty load balancer upstreams fail closed on both webservers', function (string $webserver) {
    $this->server->webserver()->update(['name' => $webserver]);
    EnvironmentDomain::factory()->for($this->lb)->create(['status' => HostedDomainStatus::ACTIVE]);
    $config = app(SyncComponentVhost::class)->generate($this->lb->fresh());
    expect($config)->toContain($webserver === 'nginx' ? 'server 127.0.0.1:9 down;' : 'respond 503');
})->with(['nginx', 'caddy']);

test('backend firewalls cannot be stopped disabled or uninstalled while needed', function (string $operation) {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    $service = $this->server->firewall();
    $action = $operation === 'uninstall' ? app(Uninstall::class) : app(Manage::class);
    expect(fn () => $action->{$operation}($service))->toThrow(ValidationException::class);
    expect($service->fresh()->status)->toBe(ServiceStatus::READY);
})->with(['stop', 'disable', 'uninstall']);

test('load balancer TLS belongs only to its server and backend PHP honors forwarded https', function (string $webserver) {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    $this->server->webserver()->update(['name' => $webserver]);
    $member->server->webserver()->update(['name' => $webserver]);
    $this->application->update(['runtime' => 'php']);
    $this->environment->update(['runtime_version' => '8.2']);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    $ssl = Ssl::factory()->create([
        'site_id' => null, 'server_id' => $this->server->id, 'environment_id' => $this->environment->id,
        'domains' => ['edge.example.com'], 'status' => SslStatus::CREATED, 'expires_at' => now()->addDay(),
    ]);
    EnvironmentDomain::factory()->for($this->lb)->create([
        'host' => 'edge.example.com', 'status' => HostedDomainStatus::ACTIVE, 'ssl_method' => SslMethod::LETSENCRYPT, 'ssl_id' => $ssl->id,
    ]);
    $config = app(SyncComponentVhost::class)->generate($this->lb->fresh());
    expect($config)->toContain($ssl->certificate_path);
    if ($webserver === 'caddy') {
        expect($config)->toContain('https://edge.example.com {', 'http://edge.example.com {');
        expect(substr_count($config, 'tls '.$ssl->certificate_path))->toBe(1);
    }
    $backend = app(SyncComponentVhost::class)->generate($this->target->fresh());
    expect($backend)->not->toContain($ssl->certificate_path, 'edge.example.com');
    expect($backend)->toContain($webserver === 'nginx' ? 'fastcgi_param HTTPS $component_' : 'env HTTPS {component_');
    $this->assertDatabaseMissing('ssls', ['server_id' => $member->server_id]);
})->with(['nginx', 'caddy']);

test('load balancer installation cannot overwrite teardown admitted during vhost sync', function () {
    $this->lb->forceFill(['status' => ComponentStatus::PENDING])->save();
    $this->mock(SyncComponentVhost::class)->shouldReceive('sync')->once()->andReturnUsing(function (Component $component): void {
        app(DeleteComponent::class)->delete($component->fresh(), []);
    });
    app(InstallComponent::class)->install($this->lb->id);
    $this->assertDatabaseHas('components', ['id' => $this->lb->id, 'status' => ComponentStatus::DELETING]);
});

test('domainless fpm backends reload their socket before old pool removal', function (bool $fails) {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    $this->application->update(['runtime' => 'php']);
    $this->environment->update(['runtime_version' => '8.3']);
    $this->target->forceFill(['user' => 'runtime', 'installed_runtime_version' => '8.2'])->save();
    $this->server->services()->create(['type' => 'php', 'name' => 'php', 'version' => '8.3', 'status' => ServiceStatus::READY]);
    $this->mock(EnsureFpmPool::class)->shouldReceive('ensure')->once();
    $synced = false;
    $this->mock(SyncComponentVhost::class)->shouldReceive('sync')->once()->andReturnUsing(function (Component $component) use ($fails, &$synced): void {
        expect($component->domains()->exists())->toBeFalse();
        expect((new SyncComponentVhost)->generate($component))->toContain('php8.3-fpm-runtime.sock');
        if ($fails) {
            throw new RuntimeException('Reload failed');
        }
        $synced = true;
    });
    $cleanup = $this->mock(InstallWebCapability::class);
    if ($fails) {
        $cleanup->shouldNotReceive('removePool');
        expect(fn () => app(SyncComponentRuntime::class)->sync($this->target->fresh()))->toThrow(RuntimeException::class);
    } else {
        $cleanup->shouldReceive('removePool')->once()->withArgs(function (Component $component, string $version) use (&$synced): bool {
            return $synced && $version === '8.2';
        });
        app(SyncComponentRuntime::class)->sync($this->target->fresh());
    }
    $this->assertDatabaseHas('components', ['id' => $this->target->id, 'installed_runtime_version' => $fails ? '8.2' : '8.3']);
})->with([false, true]);

test('new or changed memberships resync both ends without allowing removal of the previously used network', function (string $change) {
    $older = Network::factory()->create(['project_id' => $this->server->project_id, 'cidr' => '10.41.0.0/24']);
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    NetworkServer::factory()->for($older)->for($this->server)->create(['ip' => '10.41.0.1']);
    $other = $change === 'created' ? null : NetworkServer::factory()->for($older)->for($member->server)->create(['ip' => '10.41.0.2', 'status' => NetworkServerStatus::PENDING]);
    Queue::fake();
    if ($other) {
        $other->update(['status' => NetworkServerStatus::ACTIVE]);
    } else {
        NetworkServer::factory()->for($older)->for($member->server)->create(['ip' => '10.41.0.2']);
    }
    expect(app(ResolveServerAddress::class)->between($this->server, $member->server))->toBe('10.41.0.2');
    Queue::assertPushed(SyncLoadBalancerServerJob::class, fn ($job): bool => $job->serverId === $this->server->id);
    Queue::assertPushed(SyncLoadBalancerServerJob::class, fn ($job): bool => $job->serverId === $member->server_id);
    expect(fn () => app(RemoveServerFromNetwork::class)->remove($member))->toThrow(ValidationException::class);
})->with(['created', 'status']);

test('managed address removal is rejected before admission and before remote netplan work', function (bool $queued) {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    $address = ServerIpAddress::factory()->for($member->server)->create(['ip' => '10.40.0.8']);
    $member->update(['server_ip_address_id' => $address->id]);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    if ($queued) {
        $address->forceFill(['status' => IpAddressStatus::DELETING])->saveQuietly();
        $job = (new PersistServerIpsJob($member->server))->withFakeQueueInteractions();
        $job->handle();
        $job->assertFailedWith(ValidationException::class);
        expect(app(ResolveServerAddress::class)->connection($this->server, $member->server))->toBeNull();
    } else {
        expect(fn () => app(ManageServerIp::class)->delete($address))->toThrow(ValidationException::class);
        $this->assertDatabaseHas('server_ip_addresses', ['id' => $address->id, 'status' => 'configured']);
        Queue::assertNotPushed(PersistServerIpsJob::class);
    }
    expect(SSH::getExecutedCommands())->toBe([]);
})->with([false, true]);

test('observed custom address disappearance withdraws backends instead of vetoing discovery', function () {
    $member = placeLoadBalancerTargetOnNetwork($this->target, $this->server);
    $member->network->update(['type' => NetworkType::CUSTOM]);
    $address = ServerIpAddress::factory()->for($member->server)->create(['ip' => '10.40.0.8', 'is_managed' => false]);
    $member->update(['server_ip_address_id' => $address->id, 'ip' => null]);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    $address->delete();
    $this->assertDatabaseMissing('server_ip_addresses', ['id' => $address->id]);
    expect(app(ResolveServerAddress::class)->connection($this->server, $member->server))->toBeNull();
    Queue::assertPushed(SyncLoadBalancerServerJob::class, fn ($job): bool => $job->serverId === $this->server->id);
});

test('listener removal failure retains a deny tombstone ahead of broad network rules', function () {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    $rule = $this->lb->backends()->sole()->firewallRule;
    app(UpdateLoadBalancerBackends::class)->update($this->lb, ['method' => 'round-robin', 'backends' => []]);
    FirewallRule::factory()->for($this->server)->create(['type' => 'allow', 'port' => '8080', 'source' => '10.40.0.0', 'mask' => '24']);
    $this->mock(SyncComponentVhost::class)->shouldReceive('sync')->once()->andThrow(new RuntimeException('Vhost reload failed'));
    SSH::fake();
    expect(fn () => app(SyncLoadBalancerBackends::class)->sync($this->server))->toThrow(RuntimeException::class);
    $this->assertDatabaseHas('firewall_rules', ['id' => $rule->id, 'component_managed' => true, 'status' => FirewallRuleStatus::DELETING]);
    $script = implode("\n", SSH::getExecutedCommands());
    expect($script)->toContain('ufw deny from any to any proto tcp port 8080')->not->toContain('ufw allow from 127.0.0.1/32');
    expect(strpos($script, 'ufw deny from any'))->toBeLessThan(strpos($script, 'ufw allow from 10.40.0.0/24'));
    expect(fn () => app(Manage::class)->stop($this->server->firewall()))->toThrow(ValidationException::class);
    app()->forgetInstance(SyncComponentVhost::class);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    $this->assertDatabaseMissing('firewall_rules', ['id' => $rule->id]);
});

test('failed final firewall cleanup rolls custody back after listeners have been removed', function () {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    $rule = $this->lb->backends()->sole()->firewallRule;
    app(UpdateLoadBalancerBackends::class)->update($this->lb, ['method' => 'round-robin', 'backends' => []]);
    $this->mock(SyncComponentVhost::class)->shouldReceive('sync')->twice()->andReturnUsing(function (): void {
        SSH::fake()->execWillFail();
    });
    expect(fn () => app(SyncLoadBalancerBackends::class)->sync($this->server))->toThrow(SSHError::class);
    $this->assertDatabaseHas('firewall_rules', ['id' => $rule->id, 'status' => FirewallRuleStatus::DELETING]);
});

test('ordinary firewall failure cannot reactivate managed cleanup and managed mutations never queue ordinary jobs', function () {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    $managed = $this->lb->backends()->sole()->firewallRule;
    app(ManageRule::class)->delete($managed);
    Queue::assertNotPushed(ApplyRulesJob::class);
    $ordinary = FirewallRule::factory()->for($this->server)->create(['status' => FirewallRuleStatus::UPDATING]);
    (new ApplyRulesJob($ordinary))->failed(new Exception('Failed'));
    $this->assertDatabaseHas('firewall_rules', ['id' => $managed->id, 'status' => FirewallRuleStatus::DELETING]);
    $this->assertDatabaseHas('firewall_rules', ['id' => $ordinary->id, 'status' => FirewallRuleStatus::FAILED]);
});

test('managed firewall reconciliation broadcasts ready and deleted rules', function () {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    $id = $this->lb->backends()->sole()->firewall_rule_id;
    Event::assertDispatched(SocketEvent::class, fn ($event): bool => $event->data->type === 'firewall-rule.updated'
        && $event->data->data['id'] === $id && $event->data->data['status'] === 'ready' && $event->data->data['component_managed']);
    app(UpdateLoadBalancerBackends::class)->update($this->lb, ['method' => 'round-robin', 'backends' => []]);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    Event::assertDispatched(SocketEvent::class, fn ($event): bool => $event->data->type === 'firewall-rule.deleted' && $event->data->data['id'] === $id);
});

test('queued firewall shutdown rechecks newly added dependencies before touching the server', function (string $operation) {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    $service = $this->server->firewall();
    $job = $operation === 'uninstall'
        ? new UninstallJob($service, ServiceStatus::FAILED)
        : new ManageJob($service, $operation, ServiceStatus::STOPPED);
    $job->withFakeQueueInteractions()->handle();
    $job->assertFailedWith(ValidationException::class);
    $job->failed(ValidationException::withMessages(['service' => 'Required firewall']));
    $this->assertDatabaseHas('services', ['id' => $service->id]);
    expect(SSH::getExecutedCommands())->toBe([]);
})->with(['stop', 'disable', 'uninstall']);

test('admitted firewall shutdown prevents later backend admission', function (string $operation) {
    $action = $operation === 'uninstall' ? app(Uninstall::class) : app(Manage::class);
    $action->{$operation}($this->server->firewall());
    expect(fn () => app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input))->toThrow(ValidationException::class);
    $this->assertDatabaseCount('component_backends', 0);
})->with(['stop', 'disable', 'uninstall']);

test('sites cannot take a managed backend listener port', function () {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    $port = $this->site->port;
    expect(fn () => app(UpdatePort::class)->update($this->site, ['port' => 8080]))->toThrow(ValidationException::class);
    $this->assertDatabaseHas('sites', ['id' => $this->site->id, 'port' => $port]);
    expect(SSH::getExecutedCommands())->toBe([]);
});

test('environment teardown removes load balancer vhosts listeners and managed rules', function () {
    app(UpdateLoadBalancerBackends::class)->update($this->lb, $this->input);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    app(DeleteEnvironment::class)->jobs($this->environment);
    app(DeleteComponent::class)->handle($this->lb->id);
    app(DeleteComponent::class)->handle($this->target->id);
    app(SyncLoadBalancerBackends::class)->sync($this->server);
    app(DeleteEnvironment::class)->handle($this->environment->id);
    $this->assertDatabaseCount('component_backends', 0);
    $this->assertDatabaseCount('firewall_rules', 0);
    $this->assertDatabaseMissing('environments', ['id' => $this->environment->id]);
});
