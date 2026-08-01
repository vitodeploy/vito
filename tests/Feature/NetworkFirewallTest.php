<?php

use App\Actions\FirewallRule\ManageRule;
use App\Actions\Network\CreateNetwork;
use App\Actions\Network\ManageNetworkFirewallRule;
use App\Actions\Network\MaterializeServerNetworkRules;
use App\Actions\Server\EditServer;
use App\Enums\IpAddressType;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
use App\Enums\ServerNetworkRuleKind;
use App\Enums\ServerStatus;
use App\Facades\SSH;
use App\Jobs\Network\ApplyNetworkFirewallJob;
use App\Models\Network;
use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Models\ServerNetworkRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

/**
 * @param  array<int, int>  $servers
 */
function vitoPestFeatureNetworkFirewallTestWireguardNetwork(array $servers): Network
{
    return app(CreateNetwork::class)->create(test()->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => $servers,
    ]);
}

test('network is seeded with a default allow all rule', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);

    $this->assertDatabaseHas('network_firewall_rules', [
        'network_id' => $network->id,
        'name' => 'Allow all',
        'protocol' => null,
        'port' => null,
    ]);
});

test('wireguard handshake port is opened', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id, $peer->id]);

    SSH::assertExecutedContains('proto udp port 51820');
});

test('default allow all rule emits catch all', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);

    SSH::assertExecutedContains('allow from 100.64.0.0/24 to any');
});

test('allow rule is emitted scoped to the network cidr', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);

    app(ManageNetworkFirewallRule::class)->create($network, [
        'name' => 'mysql',
        'protocol' => 'tcp',
        'port' => '3306',
    ]);

    SSH::assertExecutedContains('allow from 100.64.0.0/24 to any proto tcp port 3306');
    $this->assertDatabaseHas('network_firewall_rules', [
        'network_id' => $network->id,
        'name' => 'mysql',
        'port' => '3306',
    ]);
});

test('network rules are not stored as server firewall rules', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);
    app(ManageNetworkFirewallRule::class)->create($network, [
        'name' => 'mysql',
        'protocol' => 'tcp',
        'port' => '3306',
    ]);

    expect($this->server->firewallRules()->count())->toBe(0);
});

test('server firewall page exposes managed networks', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);

    $this->actingAs($this->user);

    $this->get(route('firewall', ['server' => $this->server]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('firewall/index')
            ->where('networkRules.data.0.network_id', $network->id)
            ->where('networkRules.data.0.name', 'Allow all'));
});

test('leaving member network is not listed as managed', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);
    $network->servers()->update(['status' => NetworkServerStatus::LEAVING]);

    $this->actingAs($this->user);

    $this->get(route('firewall', ['server' => $this->server]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('firewall/index')
            ->count('networkRules.data', 0));
});

test('server level firewall change reapplies network rules', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);

    app(ManageRule::class)->create($this->server, [
        'name' => 'ssh',
        'type' => 'allow',
        'protocol' => 'tcp',
        'port' => '22',
    ]);

    SSH::assertExecutedContains('allow from 100.64.0.0/24 to any');
});

test('installing ufw opens wireguard handshake for existing member', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id, $peer->id]);

    SSH::fake();
    $this->server->firewall()->handler()->install();

    SSH::assertExecutedContains('from '.$peer->ip.'/32 to any proto udp port 51820');
});

test('failed firewall apply marks member failed', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);
    $member = $network->servers()->firstOrFail();

    (new ApplyNetworkFirewallJob($member))->failed(new RuntimeException('boom'));

    expect($member->fresh()->status)->toBe(NetworkServerStatus::FAILED);
});

test('deleting allow all locks down but keeps tunnel handshake', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id, $peer->id]);

    $allowAll = $network->firewallRules()->whereNull('protocol')->whereNull('port')->firstOrFail();
    app(ManageNetworkFirewallRule::class)->delete($allowAll);

    $rules = ServerNetworkRule::query()->where('server_id', $this->server->id);

    expect((clone $rules)->where('kind', ServerNetworkRuleKind::RULE)->exists())->toBeFalse();
    expect((clone $rules)->where('kind', ServerNetworkRuleKind::HANDSHAKE)->exists())->toBeTrue();
});

test('firewall change on offline server marks member pending', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);

    $this->server->update(['status' => ServerStatus::DISCONNECTED]);

    app(ManageNetworkFirewallRule::class)->create($network, [
        'name' => 'mysql',
        'protocol' => 'tcp',
        'port' => '3306',
    ]);

    expect($network->servers()->firstOrFail()->status)->toBe(NetworkServerStatus::PENDING);
    expect($network->fresh()->status)->toBe(NetworkStatus::SYNCING);
});

test('custom network with a cidr uses the range as the rule source', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $ip = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '10.0.0.5',
        'type' => IpAddressType::PRIVATE,
    ]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'custom-net',
        'type' => 'custom',
        'cidr' => '10.0.0.0/24',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip->id],
    ]);

    app(ManageNetworkFirewallRule::class)->create($network, [
        'name' => 'mysql',
        'protocol' => 'tcp',
        'port' => '3306',
    ]);

    SSH::assertExecutedContains('allow from 10.0.0.0/24 to any proto tcp port 3306');
    SSH::assertNotExecutedContains(
        "grep -q '^IPV6=yes'",
        'An IPv4 rule must be applied unconditionally, not behind the IPv6 guard.'
    );
});

test('custom network without a cidr uses each member private ip', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $ip1 = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '10.0.0.5',
        'type' => IpAddressType::PRIVATE,
    ]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);
    $ip2 = ServerIpAddress::factory()->create([
        'server_id' => $peer->id,
        'ip' => '10.0.0.6',
        'type' => IpAddressType::PRIVATE,
    ]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'custom-net',
        'type' => 'custom',
        'servers' => [$this->server->id, $peer->id],
        'ip_addresses' => [$this->server->id => $ip1->id, $peer->id => $ip2->id],
    ]);

    app(ManageNetworkFirewallRule::class)->create($network, [
        'name' => 'mysql',
        'protocol' => 'tcp',
        'port' => '3306',
    ]);

    SSH::assertExecutedContains('from 10.0.0.6/32 to any');
});

test('custom network applies the catch all rule on create', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $ip = ServerIpAddress::factory()->create(['server_id' => $this->server->id, 'ip' => '10.0.0.5', 'type' => IpAddressType::PRIVATE]);

    app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'custom-net',
        'type' => 'custom',
        'cidr' => '10.0.0.0/24',
        'servers' => [$this->server->id],
        'ip_addresses' => [$this->server->id => $ip->id],
    ]);

    SSH::assertExecutedContains('allow from 10.0.0.0/24 to any');
});

test('provider network never uses the vpc cidr as a rule source', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
        'type' => NetworkType::PROVIDER,
        'status' => NetworkStatus::ACTIVE,
        'cidr' => '172.31.0.0/16',
        'cidr_canonical' => '172.31.0.0/16',
    ]);

    $network->servers()->create([
        'server_id' => $this->server->id,
        'ip' => '172.31.0.5',
        'status' => NetworkServerStatus::ACTIVE,
    ]);
    $network->servers()->create([
        'server_id' => $peer->id,
        'ip' => '172.31.0.6',
        'status' => NetworkServerStatus::ACTIVE,
    ]);

    app(ManageNetworkFirewallRule::class)->create($network, [
        'name' => 'mysql',
        'protocol' => 'tcp',
        'port' => '3306',
    ]);

    $sources = ServerNetworkRule::query()
        ->where('server_id', $this->server->id)
        ->where('network_id', $network->id)
        ->pluck('source')
        ->all();

    expect($sources)->toContain('172.31.0.6');
    expect($sources)->not->toContain('172.31.0.0');

    expect(ServerNetworkRule::query()
        ->where('network_id', $network->id)
        ->where('mask', 16)
        ->count())->toBe(0, 'Provider network rules must be per-member /32s.');
});

test('server with zero networks has no materialized rules', function () {
    expect(ServerNetworkRule::query()->where('server_id', $this->server->id)->count())->toBe(0);
});

test('network create materializes handshake and catch all rows', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id, $peer->id]);
    $allowAll = $network->firewallRules()->whereNull('protocol')->whereNull('port')->firstOrFail();

    $this->assertDatabaseHas('server_network_rules', [
        'server_id' => $this->server->id,
        'network_id' => $network->id,
        'kind' => ServerNetworkRuleKind::HANDSHAKE->value,
        'protocol' => 'udp',
        'port' => (string) $network->port,
        'source' => $peer->ip,
        'network_firewall_rule_id' => null,
    ]);

    $this->assertDatabaseHas('server_network_rules', [
        'server_id' => $this->server->id,
        'network_id' => $network->id,
        'kind' => ServerNetworkRuleKind::RULE->value,
        'protocol' => null,
        'port' => null,
        'source' => '100.64.0.0',
        'network_firewall_rule_id' => $allowAll->id,
    ]);
});

/**
 * @return array<string, array{0: ?string, 1: ?string, 2: string}>
 */
dataset('protocolAndPortCombinations', function () {
    return [
        'both' => ['tcp', '3306', 'to any proto tcp port 3306'],
        'port only' => [null, '3306', 'to any port 3306'],
        'protocol only' => ['udp', null, 'to any proto udp'],
        'neither' => [null, null, 'to any'],
    ];
});

test('protocol and port are independently optional', function (?string $protocol, ?string $port, string $expected) {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id, $peer->id]);
    $network->firewallRules()->delete();

    SSH::fake();
    app(ManageNetworkFirewallRule::class)->create($network, [
        'name' => 'combo',
        'protocol' => $protocol,
        'port' => $port,
    ]);

    $this->assertDatabaseHas('network_firewall_rules', [
        'network_id' => $network->id,
        'name' => 'combo',
        'protocol' => $protocol,
        'port' => $port,
    ]);

    SSH::assertExecutedContains('allow from 100.64.0.0/24 '.$expected);
})->with('protocolAndPortCombinations');

test('port range without a protocol is rejected', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id]);

    try {
        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'range',
            'protocol' => null,
            'port' => '3000:3010',
        ]);
        $this->fail('A port range without a protocol must be rejected.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('protocol');
    }

    $this->assertDatabaseMissing('network_firewall_rules', [
        'network_id' => $network->id,
        'name' => 'range',
    ]);

    app(ManageNetworkFirewallRule::class)->create($network, [
        'name' => 'range',
        'protocol' => 'tcp',
        'port' => '3000:3010',
    ]);

    SSH::assertExecutedContains('to any proto tcp port 3000:3010');
});

test('deleting a server clears its rules from remaining members of a custom network', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    $mine = ServerIpAddress::factory()->create([
        'server_id' => $this->server->id,
        'ip' => '10.40.0.2',
        'type' => IpAddressType::PRIVATE,
    ]);
    $theirs = ServerIpAddress::factory()->create([
        'server_id' => $peer->id,
        'ip' => '10.40.0.3',
        'type' => IpAddressType::PRIVATE,
    ]);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'no-cidr-custom',
        'type' => 'custom',
        'servers' => [$this->server->id, $peer->id],
        'ip_addresses' => [$this->server->id => $mine->id, $peer->id => $theirs->id],
    ]);

    $this->assertDatabaseHas('server_network_rules', [
        'server_id' => $this->server->id,
        'network_id' => $network->id,
        'source' => '10.40.0.3',
    ]);

    $peer->delete();

    $this->assertDatabaseMissing('server_network_rules', [
        'server_id' => $this->server->id,
        'network_id' => $network->id,
        'source' => '10.40.0.3',
    ]);
});

/**
 * @return array<string, array{0: string}>
 */
dataset('untrustedServerAddresses', function () {
    return [
        'command chaining' => ['10.0.0.9; sudo ufw disable'],
        'command substitution' => ['10.0.0.9 $(id)'],
        'backticks' => ['`whoami`'],
        'newline' => ["10.0.0.9\nsudo ufw disable"],
        'not an address' => ['not-an-ip'],
        'cidr not address' => ['10.0.0.0/24'],
    ];
});

test('server address that is not an ip is rejected', function (string $ip) {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $this->expectException(ValidationException::class);

    app(EditServer::class)->edit($this->server, [
        'name' => $this->server->name,
        'ip' => $ip,
        'port' => 22,
    ]);
})->with('untrustedServerAddresses');

test('untrusted address never reaches the firewall template', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $peer = Server::factory()->create([
        'project_id' => $this->server->project_id,
        'user_id' => $this->user->id,
        'status' => ServerStatus::READY,
    ]);

    $network = vitoPestFeatureNetworkFirewallTestWireguardNetwork([$this->server->id, $peer->id]);

    Server::query()->whereKey($peer->id)->update(['ip' => '10.0.0.9; sudo ufw disable']);

    SSH::fake();
    app(MaterializeServerNetworkRules::class)->forServer($this->server->refresh());
    $this->server->firewall()?->handler()->applyRules();

    $this->assertDatabaseMissing('server_network_rules', [
        'network_id' => $network->id,
        'source' => '10.0.0.9; sudo ufw disable',
    ]);

    SSH::assertNotExecutedContains(
        'sudo ufw disable',
        'A non-address value must never be interpolated into the firewall script.'
    );
});
