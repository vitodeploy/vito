<?php

use App\Actions\Network\AddServersToNetwork;
use App\Actions\Network\CreateNetwork;
use App\Actions\Network\CreateNetworkPeer;
use App\Actions\Network\GetNetworkPeerConfig;
use App\Actions\Network\UpdateNetworkServerIp;
use App\Enums\IpAddressFamily;
use App\Enums\IpAddressType;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Models\Network;
use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Support\Cidr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function vitoPestFeatureNetworkIpv6TestReadyServer(string $ip): Server
{
    return Server::factory()->create([
        'project_id' => test()->server->project_id,
        'user_id' => test()->user->id,
        'ip' => $ip,
        'status' => ServerStatus::READY,
    ]);
}

function vitoPestFeatureNetworkIpv6TestPrivateIp(Server $server, string $ip, int $prefix): ServerIpAddress
{
    return ServerIpAddress::factory()->create([
        'server_id' => $server->id,
        'ip' => $ip,
        'prefix_length' => $prefix,
        'family' => str_contains($ip, ':') ? IpAddressFamily::V6 : IpAddressFamily::V4,
        'type' => IpAddressType::PRIVATE,
    ]);
}

test('cidr helpers handle both families', function () {
    expect(Cidr::hostPrefix('10.0.0.1'))->toBe(32);
    expect(Cidr::hostPrefix('fd00::1'))->toBe(128);
    expect(Cidr::canonical('fd00::dead:beef/64'))->toBe('fd00::/64');
    expect(Cidr::contains('fd00::/64', 'fd00::9'))->toBeTrue();
    expect(Cidr::contains('fd00::/64', 'fd00:1::9'))->toBeFalse();
    expect(Cidr::nextHost('fd00::/64', []))->toBe('fd00::2');
    expect(Cidr::nextHost('fd00::/64', ['fd00::2']))->toBe('fd00::3');
});

test('cross family comparisons never match', function () {
    expect(Cidr::overlaps('10.0.0.0/8', 'fd00::/8'))->toBeFalse();
    expect(Cidr::contains('10.0.0.0/8', 'fd00::1'))->toBeFalse();
    expect(Cidr::contains('fd00::/8', '10.0.0.1'))->toBeFalse();
});

test('endpoint brackets ipv6 only', function () {
    expect(Cidr::endpoint('1.2.3.4', 51820))->toBe('1.2.3.4:51820');
    expect(Cidr::endpoint('2001:db8::1', 51820))->toBe('[2001:db8::1]:51820');
});

test('ipv6 server endpoint is bracketed in member and peer configs', function () {
    SSH::fake();
    $first = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::1');
    $second = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::2');

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'v6-endpoints',
        'type' => 'wireguard',
        'servers' => [$first->id, $second->id],
    ]);

    $this->assertStringContainsString('Endpoint = [2001:db8::1]:'.$network->port, SSH::getUploadedContent());

    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);
    $config = app(GetNetworkPeerConfig::class)->config($peer)['config'];

    expect($config)->toMatch('/^Endpoint = \[2001:db8::1\]:'.$network->port.'$/m');
    $this->assertStringNotContainsString('Endpoint = 2001:db8::1:', $config);
});

test('ipv6 handshake rule uses a 128 prefix', function () {
    SSH::fake();
    $this->server->update(['ip' => '2001:db8::1', 'status' => ServerStatus::READY]);
    $peer = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::2');

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'v6-handshake',
        'type' => 'wireguard',
        'servers' => [$this->server->id, $peer->id],
    ]);

    $this->assertDatabaseHas('server_network_rules', [
        'network_id' => $network->id,
        'server_id' => $this->server->id,
        'source' => '2001:db8::2',
        'mask' => 128,
    ]);

    SSH::assertExecutedContains('allow from 2001:db8::2/128 to any proto udp port '.$network->port);
});

test('ipv6 rules are skipped on a member whose ufw has no ipv6', function () {
    SSH::fake();
    $this->server->update(['ip' => '2001:db8::1', 'status' => ServerStatus::READY]);
    $peer = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::2');

    app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'v6-guard',
        'type' => 'wireguard',
        'servers' => [$this->server->id, $peer->id],
    ]);

    SSH::assertExecutedContains("grep -q '^IPV6=yes' /etc/default/ufw");
});

test('custom network accepts an ipv6 range and members', function () {
    SSH::fake();
    $server = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::10');
    $ip = vitoPestFeatureNetworkIpv6TestPrivateIp($server, 'fd00:1::5', 64);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'v6-custom',
        'type' => 'custom',
        'cidr' => 'fd00:1::/64',
        'servers' => [$server->id],
        'ip_addresses' => [$server->id => $ip->id],
    ]);

    expect($network->cidr)->toBe('fd00:1::/64');
    expect($network->cidr_canonical)->toBe('fd00:1::/64');
});

test('ipv6 custom network rule uses the declared range', function () {
    SSH::fake();
    $this->server->update(['ip' => '2001:db8::21', 'status' => ServerStatus::READY]);
    $peer = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::22');
    $firstIp = vitoPestFeatureNetworkIpv6TestPrivateIp($this->server, 'fd00:2::5', 64);
    $secondIp = vitoPestFeatureNetworkIpv6TestPrivateIp($peer, 'fd00:2::6', 64);

    app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'v6-custom-rules',
        'type' => 'custom',
        'cidr' => 'fd00:2::/64',
        'servers' => [$this->server->id, $peer->id],
        'ip_addresses' => [$this->server->id => $firstIp->id, $peer->id => $secondIp->id],
    ]);

    SSH::assertExecutedContains('allow from fd00:2::/64 to any');
});

test('ufw install enables ipv6', function () {
    SSH::fake();
    $server = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::30');

    $server->services()->where('type', 'firewall')->delete();
    $service = $server->services()->create([
        'type' => 'firewall',
        'name' => 'ufw',
        'version' => 'latest',
        'status' => ServiceStatus::INSTALLING,
    ]);

    $service->handler()->install();

    SSH::assertExecutedContains("sed -i '/^IPV6=/d' /etc/default/ufw");
    SSH::assertExecutedContains("echo 'IPV6=yes' | sudo tee -a /etc/default/ufw");
});

test('ufw install opens ssh for both families', function () {
    SSH::fake();
    $server = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::31');

    $server->services()->where('type', 'firewall')->delete();
    $service = $server->services()->create([
        'type' => 'firewall',
        'name' => 'ufw',
        'version' => 'latest',
        'status' => ServiceStatus::INSTALLING,
    ]);

    $service->handler()->install();

    SSH::assertExecutedContains('ufw allow proto tcp to any port '.($server->port ?? 22));
    SSH::assertNotExecutedContains('from 0.0.0.0/0', 'The install must not scope its own allows to IPv4.');
});

test('member address outside the declared range is rejected', function () {
    SSH::fake();
    $server = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::40');
    $ip = vitoPestFeatureNetworkIpv6TestPrivateIp($server, '10.9.9.9', 32);

    $this->expectException(ValidationException::class);

    app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'mismatched',
        'type' => 'custom',
        'cidr' => '10.50.0.0/24',
        'servers' => [$server->id],
        'ip_addresses' => [$server->id => $ip->id],
    ]);
});

test('member address outside the range is rejected when adding and updating', function () {
    SSH::fake();
    $first = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::41');
    $firstIp = vitoPestFeatureNetworkIpv6TestPrivateIp($first, '10.50.0.5', 24);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'contained',
        'type' => 'custom',
        'cidr' => '10.50.0.0/24',
        'servers' => [$first->id],
        'ip_addresses' => [$first->id => $firstIp->id],
    ]);

    $second = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::42');
    $outside = vitoPestFeatureNetworkIpv6TestPrivateIp($second, '10.99.0.5', 24);

    try {
        app(AddServersToNetwork::class)->add($network, [
            'servers' => [$second->id],
            'ip_addresses' => [$second->id => $outside->id],
        ]);
        $this->fail('An address outside the declared range must be rejected.');
    } catch (ValidationException) {
        // expected
    }

    $member = $network->servers()->where('server_id', $first->id)->firstOrFail();
    $alsoOutside = vitoPestFeatureNetworkIpv6TestPrivateIp($first, '10.99.0.6', 24);

    $this->expectException(ValidationException::class);
    app(UpdateNetworkServerIp::class)->update($member, ['server_ip_address_id' => $alsoOutside->id]);
});

test('network without a range accepts any private address', function () {
    SSH::fake();
    $server = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::50');
    $ip = vitoPestFeatureNetworkIpv6TestPrivateIp($server, '10.77.0.5', 24);

    $network = app(CreateNetwork::class)->create($this->server->project, [
        'name' => 'no-range',
        'type' => 'custom',
        'cidr' => '',
        'servers' => [$server->id],
        'ip_addresses' => [$server->id => $ip->id],
    ]);

    expect($network->cidr)->toBeNull();
    expect($network->servers()->count())->toBe(1);
});

test('malformed cidr is rejected for both families', function () {
    SSH::fake();
    $server = vitoPestFeatureNetworkIpv6TestReadyServer('2001:db8::60');
    $ip = vitoPestFeatureNetworkIpv6TestPrivateIp($server, '10.0.0.5', 24);

    foreach (['10.0.0.0/33', 'fd00::/129', 'not-a-cidr', '10.0.0.0', '10.0.0.0/24; id'] as $cidr) {
        try {
            app(CreateNetwork::class)->create($this->server->project, [
                'name' => 'bad-'.md5($cidr),
                'type' => 'custom',
                'cidr' => $cidr,
                'servers' => [$server->id],
                'ip_addresses' => [$server->id => $ip->id],
            ]);
            $this->fail("Expected [$cidr] to be rejected.");
        } catch (ValidationException) {
            expect(true)->toBeTrue();
        }
    }

    expect(Network::query()->where('project_id', $this->server->project_id)->count())->toBe(0);
});
