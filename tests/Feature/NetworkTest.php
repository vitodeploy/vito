<?php

use App\Actions\Network\AddServersToNetwork;
use App\Actions\Network\CreateNetwork;
use App\Actions\Network\CreateNetworkPeer;
use App\Actions\Network\GenerateWireGuardKeys;
use App\Actions\Network\RecomputeNetworkStatus;
use App\Enums\IpAddressType;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Http\Resources\NetworkPeerResource;
use App\Http\Resources\NetworkServerResource;
use App\Models\Network;
use App\Models\NetworkFirewallRule;
use App\Models\NetworkPeer;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Models\User;
use App\Services\VPN\WireGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('network persists and casts enums', function () {
    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
    ]);

    $this->assertDatabaseHas('networks', [
        'id' => $network->id,
        'project_id' => $this->server->project_id,
        'type' => NetworkType::WIREGUARD->value,
        'status' => NetworkStatus::ACTIVE->value,
    ]);

    $network->refresh();
    expect($network->type)->toBeInstanceOf(NetworkType::class);
    expect($network->status)->toBeInstanceOf(NetworkStatus::class);
    expect($network->port)->toBe(51820);
});

test('network relations', function () {
    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
    ]);

    $member = NetworkServer::factory()->create([
        'network_id' => $network->id,
        'server_id' => $this->server->id,
        'ip' => '100.64.0.2',
    ]);

    $rule = NetworkFirewallRule::factory()->create([
        'network_id' => $network->id,
    ]);

    expect($network->servers->contains($member))->toBeTrue();
    expect($network->firewallRules->contains($rule))->toBeTrue();
    expect($this->server->project->networks->contains($network))->toBeTrue();
    expect($member->network->is($network))->toBeTrue();
    expect($member->server->is($this->server))->toBeTrue();
});

test('network server private key is encrypted at rest', function () {
    $member = NetworkServer::factory()->create([
        'network_id' => Network::factory()->create([
            'project_id' => $this->server->project_id,
        ])->id,
        'server_id' => $this->server->id,
        'ip' => '100.64.0.3',
        'private_key' => 'super-secret-private-key',
        'status' => NetworkServerStatus::PENDING,
    ]);

    $raw = DB::table('network_servers')->where('id', $member->id)->value('private_key');
    $this->assertNotSame('super-secret-private-key', $raw);
    expect($member->fresh()->private_key)->toBe('super-secret-private-key');
    expect($member->fresh()->status)->toBeInstanceOf(NetworkServerStatus::class);
});

test('generate wireguard keys produce valid curve25519 format', function () {
    $keys = app(GenerateWireGuardKeys::class)->generate();

    foreach (['private_key', 'public_key'] as $key) {
        $decoded = base64_decode($keys[$key], true);
        $this->assertNotFalse($decoded);
        expect(strlen((string) $decoded))->toBe(32);
        expect(strlen($keys[$key]))->toBe(44);
    }

    $private = base64_decode($keys['private_key']);
    expect(ord($private[0]) & 7)->toBe(0);
    expect(ord($private[31]) & 192)->toBe(64);
});

test('wireguard service installs and uninstalls via ssh', function () {
    SSH::fake('v1.0.20210914');

    $service = $this->server->services()->create([
        'type' => WireGuard::type(),
        'name' => WireGuard::id(),
        'version' => 'latest',
        'status' => ServiceStatus::INSTALLING,
    ]);

    $handler = $service->handler();
    $handler->install();
    SSH::assertExecutedContains('apt-get install -y wireguard');
    expect($handler->version())->toBe('v1.0.20210914');

    $handler->uninstall();
    SSH::assertExecutedContains('apt-get remove -y wireguard');
});

test('wireguard cannot be installed via services store', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('services.store', ['server' => $this->server]), [
        'name' => WireGuard::id(),
        'version' => 'latest',
    ])->assertSessionHasErrors('name');

    $this->assertDatabaseMissing('services', [
        'server_id' => $this->server->id,
        'name' => WireGuard::id(),
    ]);
});

test('wireguard configure network uploads conf and starts interface', function () {
    SSH::fake();

    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
        'cidr' => '100.64.0.0/24',
        'cidr_canonical' => '100.64.0.0/24',
        'port' => 51820,
    ]);

    $peerServer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
    ]);

    NetworkServer::factory()->create([
        'network_id' => $network->id,
        'server_id' => $peerServer->id,
        'ip' => '100.64.0.3',
        'public_key' => 'PEERPUBLICKEYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        'status' => NetworkServerStatus::ACTIVE,
    ]);

    $membership = NetworkServer::factory()->create([
        'network_id' => $network->id,
        'server_id' => $this->server->id,
        'ip' => '100.64.0.2',
        'private_key' => 'MYPRIVATEKEYBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB=',
        'public_key' => 'MYPUBLICKEYCCCCCCCCCCCCCCCCCCCCCCCCCCCCCCC=',
        'status' => NetworkServerStatus::UPDATING,
    ]);

    $service = $this->server->services()->create([
        'type' => WireGuard::type(),
        'name' => WireGuard::id(),
        'version' => 'latest',
        'status' => ServiceStatus::READY,
    ]);

    /** @var WireGuard $handler */
    $handler = $service->handler();
    $handler->configureNetwork($membership);

    SSH::assertFileUploaded("/etc/wireguard/wg-vito-{$network->id}.conf.tmp");
    SSH::assertExecutedContains('install -m 600 -o root -g root /etc/wireguard/wg-vito-'.$network->id.'.conf.tmp');

    $content = SSH::getUploadedContent();
    $this->assertStringContainsString('[Interface]', $content);
    $this->assertStringContainsString('Address = 100.64.0.2/24', $content);
    $this->assertStringContainsString('ListenPort = 51820', $content);
    $this->assertStringContainsString('[Peer]', $content);
    $this->assertStringContainsString('PEERPUBLICKEYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', $content);
    $this->assertStringContainsString('AllowedIPs = 100.64.0.3/32', $content);
    $this->assertStringContainsString('Endpoint = '.$peerServer->ip.':51820', $content);

    SSH::assertExecutedContains("wg-quick@wg-vito-{$network->id}");
});

test('store creates wireguard network and redirects', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $this->actingAs($this->user);

    $response = $this->post(route('networks.store'), [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('networks', [
        'name' => 'wg-net',
        'type' => NetworkType::WIREGUARD->value,
        'project_id' => $this->server->project_id,
    ]);
});

test('add server via http rejects cross project server', function () {
    SSH::fake();
    $network = Network::factory()->create(['project_id' => $this->server->project_id]);

    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();
    $foreign = Server::factory()->create([
        'project_id' => $otherUser->current_project_id,
        'user_id' => $otherUser->id,
    ]);

    $this->actingAs($this->user);

    $this->post(route('networks.servers.store', $network), [
        'servers' => [$foreign->id],
    ])->assertSessionHasErrors('servers.0');

    $this->assertDatabaseMissing('network_servers', ['server_id' => $foreign->id]);
});

test('destroy network via http', function () {
    SSH::fake();
    $network = Network::factory()->create(['project_id' => $this->server->project_id]);

    $this->actingAs($this->user);

    $this->delete(route('networks.destroy', $network))->assertRedirect(route('networks'));
    $this->assertDatabaseMissing('networks', ['id' => $network->id]);
});

test('cannot view another projects network', function () {
    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();
    $network = Network::factory()->create(['project_id' => $otherUser->current_project_id]);

    $this->actingAs($this->user);

    $this->get(route('networks.show', $network))->assertForbidden();
});

test('network server resource never exposes private key', function () {
    $network = Network::factory()->create(['project_id' => $this->server->project_id]);
    $member = NetworkServer::factory()->create([
        'network_id' => $network->id,
        'server_id' => $this->server->id,
        'ip' => '100.64.0.9',
        'private_key' => 'top-secret',
    ]);

    $data = (new NetworkServerResource($member->load('server')))->toArray(request());

    $this->assertArrayNotHasKey('private_key', $data);
    expect($data)->toHaveKey('public_key');
});

test('network peer is never serialised with its private key', function () {
    $network = Network::factory()->create(['project_id' => $this->server->project_id]);
    $peer = NetworkPeer::factory()->create([
        'network_id' => $network->id,
        'private_key' => 'top-secret',
    ]);

    $this->assertArrayNotHasKey('private_key', (new NetworkPeerResource($peer))->toArray(request()));
    $this->assertArrayNotHasKey('private_key', $peer->toArray());
    expect($peer->private_key)->toBe('top-secret');
});

test('firewall rule update via http', function () {
    SSH::fake();
    $network = Network::factory()->create(['project_id' => $this->server->project_id]);
    $rule = NetworkFirewallRule::factory()->create(['network_id' => $network->id, 'port' => '80']);

    $this->actingAs($this->user);

    $this->put(route('networks.firewall.update', ['network' => $network, 'networkFirewallRule' => $rule]), [
        'name' => 'updated',
        'protocol' => 'tcp',
        'port' => '443',
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('network_firewall_rules', ['id' => $rule->id, 'name' => 'updated', 'port' => '443']);
});

test('empty cidr is stored as null not canonicalised', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $ip = ServerIpAddress::factory()->create(['server_id' => $this->server->id, 'ip' => '10.0.0.5', 'type' => IpAddressType::PRIVATE]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'no-cidr-net',
        'type' => 'custom',
        'cidr' => '',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip->id],
    ]);

    $this->assertDatabaseHas('networks', [
        'id' => $network->id,
        'cidr' => null,
        'cidr_canonical' => null,
    ]);
});

test('update provider network server ip via http', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $ip1 = ServerIpAddress::factory()->create(['server_id' => $this->server->id, 'ip' => '10.0.0.5', 'type' => IpAddressType::PRIVATE]);
    $ip2 = ServerIpAddress::factory()->create(['server_id' => $this->server->id, 'ip' => '10.0.0.6', 'type' => IpAddressType::PRIVATE]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'prov-net',
        'type' => 'custom',
        'cidr' => '10.0.0.0/24',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip1->id],
    ]);
    $member = $network->servers()->firstOrFail();

    $this->actingAs($this->user);

    $this->put(route('networks.servers.update', ['network' => $network, 'networkServer' => $member]), [
        'server_ip_address_id' => $ip2->id,
    ])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('network_servers', ['id' => $member->id, 'server_ip_address_id' => $ip2->id]);
});

test('cannot update ip on wireguard network server', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $ip = ServerIpAddress::factory()->create(['server_id' => $this->server->id, 'ip' => '10.0.0.6', 'type' => IpAddressType::PRIVATE]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $member = $network->servers()->firstOrFail();

    $this->actingAs($this->user);

    $this->put(route('networks.servers.update', ['network' => $network, 'networkServer' => $member]), [
        'server_ip_address_id' => $ip->id,
    ])->assertNotFound();
});

test('adding a server moves the network off a port it already uses', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $other = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    $first = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-first',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $second = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-second',
        'type' => 'wireguard',
        'servers' => [$other->id],
    ]);

    expect($first->port)->toBe(51820);
    expect($second->port)->toBe(51820);

    app(AddServersToNetwork::class)->add($second, ['servers' => [$this->server->id]]);

    expect($second->fresh()?->port)->toBe(51821);
    expect($first->fresh()?->port)->toBe(51820);
    expect($second->servers()->count())->toBe(2);
});

test('port move warns that peers must download their config again', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $other = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-first',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $second = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-second',
        'type' => 'wireguard',
        'servers' => [$other->id],
    ]);

    app(CreateNetworkPeer::class)->create($second, ['name' => 'laptop']);

    $this->actingAs($this->user);

    $this->post(route('networks.servers.store', ['network' => $second]), [
        'servers' => [$this->server->id],
    ])->assertSessionHas('warning');

    expect($second->fresh()?->port)->toBe(51821);
});

test('a range outside private address space is rejected', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $ip = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '10.80.0.5',
        'type' => IpAddressType::PRIVATE,
    ]);

    foreach (['0.0.0.0/0', '::/0', '8.8.8.0/24', '10.0.0.0/7', '2001:db8::/32'] as $cidr) {
        try {
            app(CreateNetwork::class)->create($this->server->project, [
                'name' => 'public-'.md5($cidr),
                'type' => 'custom',
                'cidr' => $cidr,
                'servers' => [$this->server->id],
                'ip_addresses' => [$this->server->id => $ip->id],
            ]);
            $this->fail("Expected [$cidr] to be rejected.");
        } catch (ValidationException) {
            // expected
        }
    }

    expect(Network::query()->where('project_id', $this->server->project_id)->count())->toBe(0);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'private-range',
        'type' => 'custom',
        'cidr' => '10.80.0.0/24',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip->id],
    ]);

    expect($network->cidr)->toBe('10.80.0.0/24');
});

test('malformed servers input is a validation error', function () {
    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
        'type' => NetworkType::CUSTOM,
    ]);

    $this->actingAs($this->user);

    $this->post(route('networks.store'), [
        'name' => 'bad-input',
        'type' => 'custom',
        'servers' => [['nested']],
        'ip_addresses' => [],
    ])->assertSessionHasErrors('servers.0');

    $this->post(route('networks.servers.store', ['network' => $network]), [
        'servers' => [['nested']],
        'ip_addresses' => [],
    ])->assertSessionHasErrors('servers.0');

    $this->assertDatabaseMissing('networks', ['name' => 'bad-input']);
});

test('sub resource 404 when not belonging to network', function () {
    $network = Network::factory()->create(['project_id' => $this->server->project_id]);
    $other = Network::factory()->create(['project_id' => $this->server->project_id]);
    $rule = NetworkFirewallRule::factory()->create(['network_id' => $other->id]);

    $this->actingAs($this->user);

    $this->delete(route('networks.firewall.destroy', ['network' => $network, 'networkFirewallRule' => $rule]))
        ->assertNotFound();
});

test('update network rename via http', function () {
    $network = Network::factory()->create(['project_id' => $this->server->project_id, 'name' => 'old-name']);

    $this->actingAs($this->user);

    $this->put(route('networks.update', $network), ['name' => 'new-name'])->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('networks', ['id' => $network->id, 'name' => 'new-name']);
});

test('viewer role cannot create network', function () {
    $viewer = User::factory()->create();
    $this->server->project->users()->create(['user_id' => $viewer->id, 'role' => UserRole::USER]);
    $viewer->current_project_id = $this->server->project_id;
    $viewer->save();

    $this->actingAs($viewer);

    $this->post(route('networks.store'), [
        'name' => 'denied',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ])->assertForbidden();
});

test('member regenerate via http resyncs the member', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
        'cidr' => '100.64.0.0/24',
        'cidr_canonical' => '100.64.0.0/24',
    ]);
    $member = NetworkServer::factory()->create([
        'network_id' => $network->id,
        'server_id' => $this->server->id,
        'ip' => '100.64.0.5',
        'status' => NetworkServerStatus::FAILED,
        'sync_attempts' => 3,
    ]);

    $this->actingAs($this->user);

    $this->post(route('networks.servers.sync', ['network' => $network, 'networkServer' => $member]))
        ->assertSessionDoesntHaveErrors();

    $fresh = $member->fresh();
    expect($fresh?->status)->toBe(NetworkServerStatus::ACTIVE);
    expect($fresh?->sync_attempts)->toBe(0);
    SSH::assertExecutedContains('wg-quick@wg-vito-'.$network->id);
});

test('custom network duplicate cidr is rejected', function () {
    $ip = ServerIpAddress::factory()->create(['server_id' => $this->server->id, 'ip' => '10.0.0.5', 'type' => IpAddressType::PRIVATE]);
    Network::factory()->create([
        'project_id' => $this->server->project_id,
        'type' => NetworkType::CUSTOM,
        'cidr' => '10.0.0.0/24',
        'cidr_canonical' => '10.0.0.0/24',
    ]);

    $this->actingAs($this->user);

    $this->post(route('networks.store'), [
        'name' => 'dup',
        'type' => 'custom',
        'cidr' => '10.0.0.5/24',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip->id],
    ])->assertSessionHasErrors('cidr');
});

test('network sync logs are associated to the network', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'network_id' => $network->id,
        'type' => 'configure-wireguard-'.$network->id,
    ]);
    $this->assertDatabaseHas('server_logs', [
        'network_id' => $network->id,
        'type' => 'apply-rules',
    ]);
    expect(DB::table('server_logs')->whereNull('network_id')->count())->toBe(0);
});

test('network without servers is not reported as active', function () {
    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
        'status' => NetworkStatus::ACTIVE,
    ]);

    app(RecomputeNetworkStatus::class)->handle($network);

    expect($network->fresh()?->status)->toBe(NetworkStatus::CREATING);
});

test('index lists servers as options with their private ips', function () {
    $ip = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '10.0.0.7',
        'type' => IpAddressType::PRIVATE,
    ]);

    $this->actingAs($this->user);

    $this->get(route('networks'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('networks/index')
            ->has('servers.0', fn (AssertableInertia $option) => $option
                ->where('id', $this->server->id)
                ->where('name', $this->server->name)
                ->has('is_ready')
                ->has('private_ips.0', fn (AssertableInertia $address) => $address
                    ->where('id', $ip->id)
                    ->where('ip', '10.0.0.7')
                    ->has('is_primary')))
            ->etc());
});

test('servers page lists member ips for a custom network', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $ip = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '10.60.0.5',
        'type' => IpAddressType::PRIVATE,
    ]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'custom-members',
        'type' => 'custom',
        'cidr' => '10.60.0.0/24',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip->id],
    ]);

    $this->actingAs($this->user);

    $this->get(route('networks.servers', $network))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('networks/servers')
            ->has('memberIps.0', fn (AssertableInertia $member) => $member
                ->where('server_id', $this->server->id)
                ->where('server_name', $this->server->name)
                ->where('ip_address_id', $ip->id)
                ->has('private_ips.0')
                ->etc())
            ->etc());
});

test('overview returns stats and recent logs', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $this->actingAs($this->user);

    $this->get(route('networks.show', $network))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('networks/show')
            ->where('stats.servers', 1)
            ->where('stats.peers', 0)
            ->where('stats.firewall_rules', 1)
            ->has('logs.data.0', fn (AssertableInertia $log) => $log
                ->where('network_id', $network->id)
                ->etc()));
});

test('logs page returns network logs with their server', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $this->actingAs($this->user);

    $this->get(route('networks.logs', $network))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('networks/logs')
            ->has('logs.data.0', fn (AssertableInertia $log) => $log
                ->where('network_id', $network->id)
                ->where('server_id', $this->server->id)
                ->where('server_name', $this->server->name)
                ->etc()));
});

test('logs page is not visible to other projects', function () {
    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
        'type' => NetworkType::CUSTOM,
    ]);

    $outsider = User::factory()->create();
    $outsider->ensureHasDefaultProject();

    $this->actingAs($outsider)
        ->get(route('networks.logs', $network))
        ->assertForbidden();
});
