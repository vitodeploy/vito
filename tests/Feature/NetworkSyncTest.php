<?php

use App\Actions\Network\AddServersToNetwork;
use App\Actions\Network\AllocateNetworkBlock;
use App\Actions\Network\CreateNetwork;
use App\Actions\Network\CreateNetworkPeer;
use App\Actions\Network\DeleteNetwork;
use App\Actions\Network\RemoveServerFromNetwork;
use App\Actions\Network\SyncNetwork;
use App\Actions\ServerIp\ManageServerIp;
use App\Actions\ServerIp\RefreshServerIps;
use App\Enums\IpAddressFamily;
use App\Enums\IpAddressType;
use App\Enums\NetworkAddressingPool;
use App\Enums\NetworkPeerStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Network\SyncNetworkServerJob;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Models\User;
use App\Support\Cidr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('cidr helpers', function () {
    expect(Cidr::canonical('100.64.0.5/24'))->toBe('100.64.0.0/24');
    expect(Cidr::overlaps('10.0.0.0/16', '10.0.5.0/24'))->toBeTrue();
    expect(Cidr::overlaps('10.0.0.0/24', '10.1.0.0/24'))->toBeFalse();
    expect(Cidr::nextHost('100.64.0.0/24', []))->toBe('100.64.0.2');
    expect(Cidr::nextHost('100.64.0.0/24', ['100.64.0.2']))->toBe('100.64.0.3');
});

test('block allocator skips member subnet', function () {
    ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '100.64.0.10',
        'prefix_length' => 24,
        'type' => IpAddressType::PUBLIC,
    ]);

    $cidr = app(AllocateNetworkBlock::class)->allocate(
        NetworkAddressingPool::CGNAT,
        24,
        collect(),
        collect([$this->server])
    );

    expect($cidr)->toBe('100.64.1.0/24');
});

test('create wireguard network allocates block and activates ready member', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    expect((string) $network->cidr)->toStartWith('100.64.');

    $member = $network->servers()->firstOrFail();
    expect($member->ip)->toBe('100.64.0.2');
    expect($member->public_key)->not->toBeNull();
    expect($member->fresh()->status)->toBe(NetworkServerStatus::ACTIVE);
    expect($network->fresh()->status)->toBe(NetworkStatus::ACTIVE);
    expect($this->server->fresh()->service('vpn'))->not->toBeNull();
    SSH::assertExecutedContains('wg-quick@wg-vito-'.$network->id);
});

test('create wireguard member pending when server not ready then reconciler activates', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::DISCONNECTED]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $member = $network->servers()->firstOrFail();
    expect($member->status)->toBe(NetworkServerStatus::PENDING);
    expect($network->fresh()->status)->toBe(NetworkStatus::SYNCING);

    $this->server->update(['status' => ServerStatus::READY]);
    Artisan::call('networks:reconcile');

    expect($member->fresh()->status)->toBe(NetworkServerStatus::ACTIVE);
    expect($network->fresh()->status)->toBe(NetworkStatus::ACTIVE);
});

test('custom network members are active without wireguard', function () {
    SSH::fake();

    $ip = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '10.0.0.5',
        'type' => IpAddressType::PRIVATE,
    ]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'prov-net',
        'type' => 'custom',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip->id],
    ]);

    $member = $network->servers()->firstOrFail();
    expect($member->status)->toBe(NetworkServerStatus::ACTIVE);
    expect($member->server_ip_address_id)->toBe($ip->id);
    expect($network->fresh()->status)->toBe(NetworkStatus::ACTIVE);
    expect($this->server->fresh()->service('vpn'))->toBeNull();
    SSH::assertNotExecutedContains('wg-quick');
});

test('create rejects zero servers', function () {
    $this->expectException(ValidationException::class);

    app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'empty',
        'type' => 'wireguard',
        'servers' => [],
    ]);
});

test('custom network rejects a public ip', function () {
    $ip = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '1.2.3.4',
        'type' => IpAddressType::PUBLIC,
    ]);

    $this->expectException(ValidationException::class);

    app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'prov-net',
        'type' => 'custom',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip->id],
    ]);
});

test('remove last wireguard member tears down and uninstalls', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $member = $network->servers()->firstOrFail();

    app(RemoveServerFromNetwork::class)->remove($member->fresh());

    $this->assertDatabaseMissing('network_servers', ['id' => $member->id]);
    expect($this->server->fresh()->service('vpn'))->toBeNull();
    SSH::assertExecutedContains('wg-quick down wg-vito-'.$network->id);
    SSH::assertExecutedContains('apt-get remove -y wireguard');
});

test('delete network removes members and network', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    app(DeleteNetwork::class)->delete($network);

    $this->assertDatabaseMissing('networks', ['id' => $network->id]);
    $this->assertDatabaseMissing('network_servers', ['network_id' => $network->id]);
});

test('teardown failure recomputes network status', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $member = $network->servers()->firstOrFail();

    NetworkServer::query()->whereKey($member->id)->update([
        'status' => NetworkServerStatus::LEAVING,
    ]);

    (new SyncNetworkServerJob($member->fresh(), true))->failed(new Exception('boom'));

    $this->assertDatabaseHas('network_servers', ['id' => $member->id]);
    expect($network->fresh()->status)->toBe(NetworkStatus::SYNCING);
});

test('reconciler keeps retrying a leaving member past the fast attempts', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $member = $network->servers()->firstOrFail();

    NetworkServer::query()->whereKey($member->id)->update([
        'status' => NetworkServerStatus::LEAVING,
        'sync_attempts' => 5,
        'updated_at' => now()->subMinutes(3),
    ]);

    Artisan::call('networks:reconcile');

    $this->assertDatabaseHas('network_servers', ['id' => $member->id]);

    NetworkServer::query()->whereKey($member->id)->update([
        'status' => NetworkServerStatus::LEAVING,
        'updated_at' => now()->subMinutes(61),
    ]);

    Artisan::call('networks:reconcile');

    $this->assertDatabaseMissing('network_servers', ['id' => $member->id]);
    $this->assertDatabaseMissing('server_logs', [
        'network_id' => $network->id,
        'type' => 'network-leave-incomplete',
    ]);
});

test('reconciler force converges a leaving member that never converges', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $member = $network->servers()->firstOrFail();

    NetworkServer::query()->whereKey($member->id)->update([
        'status' => NetworkServerStatus::LEAVING,
        'sync_attempts' => 30,
        'updated_at' => now()->subMinutes(3),
    ]);

    Artisan::call('networks:reconcile');

    $this->assertDatabaseMissing('network_servers', ['id' => $member->id]);
    $this->assertDatabaseHas('server_logs', [
        'network_id' => $network->id,
        'type' => 'network-leave-incomplete',
    ]);
});

test('create wireguard rejects when block is full', function () {
    SSH::fake();

    $servers = [$this->server->id];
    for ($i = 0; $i < 13; $i++) {
        $servers[] = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::DISCONNECTED,
        ])->id;
    }

    try {
        app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-full',
            'type' => 'wireguard',
            'prefix' => 28,
            'servers' => $servers,
        ]);
        $this->fail('Expected a full-block ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('servers');
    }

    $this->assertDatabaseMissing('networks', ['name' => 'wg-full']);
});

test('add server to wireguard network assigns distinct ip', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $server2 = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    app(AddServersToNetwork::class)->add($network, ['servers' => [$server2->id]]);

    $ips = $network->servers()->pluck('ip');
    expect($ips->count())->toBe(2);
    expect($ips->unique()->count())->toBe($ips->count());
    expect($ips->all())->toContain('100.64.0.3');
});

test('add server rejects cross project server', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();
    $foreign = Server::factory()->create([
        'project_id' => $otherUser->current_project_id,
        'user_id' => $otherUser->id,
        'status' => ServerStatus::READY,
    ]);

    $this->expectException(ValidationException::class);

    app(AddServersToNetwork::class)->add($network, ['servers' => [$foreign->id]]);
});

test('sync reinstalls wireguard when service not ready', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $this->server->services()->create([
        'type' => 'vpn',
        'name' => 'wireguard',
        'version' => 'latest',
        'status' => ServiceStatus::INSTALLATION_FAILED,
    ]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    SSH::assertExecutedContains('apt-get install -y wireguard');
    expect($this->server->fresh()->service('vpn')->status)->toBe(ServiceStatus::READY);
    expect($network->servers()->firstOrFail()->status)->toBe(NetworkServerStatus::ACTIVE);
});

test('reconciler retries failed member when server ready', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $member = $network->servers()->firstOrFail();

    NetworkServer::query()->whereKey($member->id)->update([
        'status' => NetworkServerStatus::FAILED,
        'sync_attempts' => 1,
    ]);

    Artisan::call('networks:reconcile');

    $fresh = $member->fresh();
    expect($fresh->status)->toBe(NetworkServerStatus::ACTIVE);
    expect($fresh->sync_attempts)->toBe(0);
});

test('reconciler retries an exhausted member on the slow cadence', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $member = $network->servers()->firstOrFail();

    NetworkServer::query()->whereKey($member->id)->update([
        'status' => NetworkServerStatus::FAILED,
        'sync_attempts' => 5,
        'updated_at' => now()->subMinutes(5),
    ]);

    Artisan::call('networks:reconcile');

    expect($member->fresh()?->status)->toBe(NetworkServerStatus::FAILED, 'An exhausted member must not be retried again on the fast cadence.');

    NetworkServer::query()->whereKey($member->id)->update(['updated_at' => now()->subMinutes(61)]);

    Artisan::call('networks:reconcile');

    expect($member->fresh()?->status)->toBe(NetworkServerStatus::ACTIVE);
});

test('refresh removes a custom membership when its address disappears', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $address = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '10.70.0.5',
        'prefix_length' => 24,
        'family' => IpAddressFamily::V4,
        'type' => IpAddressType::PRIVATE,
        'is_managed' => false,
    ]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'custom-net',
        'type' => 'custom',
        'cidr' => '10.70.0.0/24',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $address->id],
    ]);

    expect($network->servers()->count())->toBe(1);

    SSH::fake(json_encode([[
        'ifname' => 'eth0',
        'addr_info' => [[
            'family' => 'inet',
            'local' => '10.70.0.9',
            'prefixlen' => 24,
            'scope' => 'global',
        ]],
    ]]));

    app(RefreshServerIps::class)->handle($this->server);

    $this->assertDatabaseMissing('server_ip_addresses', ['id' => $address->id]);
    expect($network->servers()->where('status', '!=', NetworkServerStatus::LEAVING)->count())->toBe(0);
});

test('promoting a public ip resyncs wireguard members', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $other = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id, $other->id],
    ]);

    $promoted = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '203.0.113.9',
        'family' => IpAddressFamily::V4,
        'type' => IpAddressType::PUBLIC,
    ]);

    SSH::fake();

    app(ManageServerIp::class)->setPrimary($promoted);

    expect($this->server->fresh()?->ip)->toBe('203.0.113.9');
    $this->assertStringContainsString(
        'Endpoint = 203.0.113.9:'.$network->port,
        SSH::getUploadedContent(),
        "The other member's tunnel must point at the new address."
    );
});

test('deleting the last member server recomputes the network', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    expect($network->fresh()?->status)->toBe(NetworkStatus::ACTIVE);
    expect($peer->fresh()?->status)->toBe(NetworkPeerStatus::ACTIVE);

    $this->server->delete();

    expect($network->servers()->count())->toBe(0);
    expect($network->fresh()?->status)->toBe(NetworkStatus::CREATING);
    expect($peer->fresh()?->status)->toBe(NetworkPeerStatus::PENDING);
});

test('deleting server resyncs wireguard siblings', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $server2 = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id, $server2->id],
    ]);
    $sibling = $network->servers()->where('server_id', $server2->id)->firstOrFail();
    $departingKey = (string) $network->servers()->where('server_id', $this->server->id)->firstOrFail()->public_key;

    SSH::fake();

    $this->server->deleteFromProvider = false;
    $this->server->delete();

    $this->assertDatabaseMissing('network_servers', ['server_id' => $this->server->id]);
    expect($network->servers()->count())->toBe(1);
    expect($sibling->fresh()->status)->toBe(NetworkServerStatus::ACTIVE);

    SSH::assertExecutedContains('wg-quick@wg-vito-'.$network->id);
    $this->assertStringNotContainsString(
        $departingKey,
        SSH::getUploadedContent(),
        "The sibling's tunnel must be rewritten without the departed server's key."
    );
});

test('leaving one of two wireguard networks keeps service installed', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $networkA = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-a',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-b',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    $memberA = $networkA->servers()->firstOrFail();
    app(RemoveServerFromNetwork::class)->remove($memberA->fresh());

    $this->assertDatabaseMissing('network_servers', ['id' => $memberA->id]);
    expect($this->server->fresh()->service('vpn'))->not->toBeNull();
});

test('reconciler recovers stale updating member', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $member = $network->servers()->firstOrFail();

    NetworkServer::query()->whereKey($member->id)->update([
        'status' => NetworkServerStatus::UPDATING,
        'updated_at' => now()->subMinutes(91),
    ]);

    Artisan::call('networks:reconcile');

    expect($member->fresh()->status)->toBe(NetworkServerStatus::ACTIVE);
});

test('allocator ignores ipv6 member address', function () {
    ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => 'fe80::1',
        'prefix_length' => 64,
        'family' => IpAddressFamily::V6,
        'type' => IpAddressType::PUBLIC,
    ]);

    $cidr = app(AllocateNetworkBlock::class)->allocate(
        NetworkAddressingPool::CGNAT,
        24,
        collect(),
        collect([$this->server])
    );

    expect($cidr)->toBe('100.64.0.0/24');
});

test('two wireguard networks sharing a server get distinct ports', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $a = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-a',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $b = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-b',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);

    expect($a->port)->toBe(51820);
    expect($b->port)->toBe(51821);
});

test('custom network accepts a second server', function () {
    SSH::fake();
    $ip1 = ServerIpAddress::factory()->create(['server_id' => $this->server->id, 'ip' => '10.0.0.5', 'type' => IpAddressType::PRIVATE]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'prov',
        'type' => 'custom',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip1->id],
    ]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);
    $ip2 = ServerIpAddress::factory()->create(['server_id' => $peer->id, 'ip' => '10.0.0.6', 'type' => IpAddressType::PRIVATE]);

    app(AddServersToNetwork::class)->add($network, ['servers' => [$peer->id], 'ip_addresses' => [$peer->id => $ip2->id]]);

    expect($network->servers()->count())->toBe(2);
    expect($network->servers()->where('server_id', $peer->id)->firstOrFail()->status)->toBe(NetworkServerStatus::ACTIVE);
});

test('sync network member regenerates config', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => [$this->server->id],
    ]);
    $member = $network->servers()->firstOrFail();

    app(SyncNetwork::class)->member($member->fresh());

    expect($member->fresh()->status)->toBe(NetworkServerStatus::ACTIVE);
    SSH::assertExecutedContains('wg-quick@wg-vito-'.$network->id);
});
