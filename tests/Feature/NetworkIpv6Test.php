<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class NetworkIpv6Test extends TestCase
{
    use RefreshDatabase;

    private function readyServer(string $ip): Server
    {
        return Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'ip' => $ip,
            'status' => ServerStatus::READY,
        ]);
    }

    private function privateIp(Server $server, string $ip, int $prefix): ServerIpAddress
    {
        return ServerIpAddress::factory()->create([
            'server_id' => $server->id,
            'ip' => $ip,
            'prefix_length' => $prefix,
            'family' => str_contains($ip, ':') ? IpAddressFamily::V6 : IpAddressFamily::V4,
            'type' => IpAddressType::PRIVATE,
        ]);
    }

    public function test_cidr_helpers_handle_both_families(): void
    {
        $this->assertSame(32, Cidr::hostPrefix('10.0.0.1'));
        $this->assertSame(128, Cidr::hostPrefix('fd00::1'));
        $this->assertSame('fd00::/64', Cidr::canonical('fd00::dead:beef/64'));
        $this->assertTrue(Cidr::contains('fd00::/64', 'fd00::9'));
        $this->assertFalse(Cidr::contains('fd00::/64', 'fd00:1::9'));
        $this->assertSame('fd00::2', Cidr::nextHost('fd00::/64', []));
        $this->assertSame('fd00::3', Cidr::nextHost('fd00::/64', ['fd00::2']));
    }

    /**
     * A family mismatch must never read as containment — an IPv4 pool and an IPv6 range
     * would otherwise look like they overlap and block allocation.
     */
    public function test_cross_family_comparisons_never_match(): void
    {
        $this->assertFalse(Cidr::overlaps('10.0.0.0/8', 'fd00::/8'));
        $this->assertFalse(Cidr::contains('10.0.0.0/8', 'fd00::1'));
        $this->assertFalse(Cidr::contains('fd00::/8', '10.0.0.1'));
    }

    public function test_endpoint_brackets_ipv6_only(): void
    {
        $this->assertSame('1.2.3.4:51820', Cidr::endpoint('1.2.3.4', 51820));
        $this->assertSame('[2001:db8::1]:51820', Cidr::endpoint('2001:db8::1', 51820));
    }

    /**
     * WireGuard rejects a bare `host:port` for IPv6 — the address has to be bracketed or the
     * config will not parse on the member or in a downloaded peer config.
     */
    public function test_ipv6_server_endpoint_is_bracketed_in_member_and_peer_configs(): void
    {
        SSH::fake();
        $first = $this->readyServer('2001:db8::1');
        $second = $this->readyServer('2001:db8::2');

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'v6-endpoints',
            'type' => 'wireguard',
            'servers' => [$first->id, $second->id],
        ]);

        $this->assertStringContainsString('Endpoint = [2001:db8::1]:'.$network->port, SSH::getUploadedContent());

        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);
        $config = app(GetNetworkPeerConfig::class)->config($peer)['config'];

        $this->assertMatchesRegularExpression('/^Endpoint = \[2001:db8::1\]:'.$network->port.'$/m', $config);
        $this->assertStringNotContainsString('Endpoint = 2001:db8::1:', $config);
    }

    /**
     * The handshake rule's source is the peer's public address, so an IPv6 endpoint needs a
     * /128 host prefix rather than the IPv4 /32.
     */
    public function test_ipv6_handshake_rule_uses_a_128_prefix(): void
    {
        SSH::fake();
        $this->server->update(['ip' => '2001:db8::1', 'status' => ServerStatus::READY]);
        $peer = $this->readyServer('2001:db8::2');

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
    }

    /**
     * ufw refuses a v6 rule when its own IPv6 support is off, and rules are applied as one
     * reset-and-reapply batch — so an unguarded v6 rule would fail the whole batch on an
     * IPv4-only member and leave it permanently `failed`. With IPv6 off the kernel table is
     * unfiltered anyway, so skipping the rule loses nothing.
     */
    public function test_ipv6_rules_are_skipped_on_a_member_whose_ufw_has_no_ipv6(): void
    {
        SSH::fake();
        $this->server->update(['ip' => '2001:db8::1', 'status' => ServerStatus::READY]);
        $peer = $this->readyServer('2001:db8::2');

        app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'v6-guard',
            'type' => 'wireguard',
            'servers' => [$this->server->id, $peer->id],
        ]);

        SSH::assertExecutedContains("grep -q '^IPV6=yes' /etc/default/ufw");
    }

    public function test_custom_network_accepts_an_ipv6_range_and_members(): void
    {
        SSH::fake();
        $server = $this->readyServer('2001:db8::10');
        $ip = $this->privateIp($server, 'fd00:1::5', 64);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'v6-custom',
            'type' => 'custom',
            'cidr' => 'fd00:1::/64',
            'servers' => [$server->id],
            'ip_addresses' => [$server->id => $ip->id],
        ]);

        $this->assertSame('fd00:1::/64', $network->cidr);
        $this->assertSame('fd00:1::/64', $network->cidr_canonical);
    }

    public function test_ipv6_custom_network_rule_uses_the_declared_range(): void
    {
        SSH::fake();
        $this->server->update(['ip' => '2001:db8::21', 'status' => ServerStatus::READY]);
        $peer = $this->readyServer('2001:db8::22');
        $firstIp = $this->privateIp($this->server, 'fd00:2::5', 64);
        $secondIp = $this->privateIp($peer, 'fd00:2::6', 64);

        app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'v6-custom-rules',
            'type' => 'custom',
            'cidr' => 'fd00:2::/64',
            'servers' => [$this->server->id, $peer->id],
            'ip_addresses' => [$this->server->id => $firstIp->id, $peer->id => $secondIp->id],
        ]);

        SSH::assertExecutedContains('allow from fd00:2::/64 to any');
    }

    public function test_ufw_install_enables_ipv6(): void
    {
        SSH::fake();
        $server = $this->readyServer('2001:db8::30');

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
    }

    /**
     * The install allows SSH before enabling the firewall. Scoping that allow to an IPv4 source
     * would leave a default-deny v6 table with no way in, locking Vito out of a server it
     * reaches over IPv6 — and the very next thing install does is open a new connection.
     */
    public function test_ufw_install_opens_ssh_for_both_families(): void
    {
        SSH::fake();
        $server = $this->readyServer('2001:db8::31');

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
    }

    public function test_member_address_outside_the_declared_range_is_rejected(): void
    {
        SSH::fake();
        $server = $this->readyServer('2001:db8::40');
        $ip = $this->privateIp($server, '10.9.9.9', 32);

        $this->expectException(ValidationException::class);

        app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'mismatched',
            'type' => 'custom',
            'cidr' => '10.50.0.0/24',
            'servers' => [$server->id],
            'ip_addresses' => [$server->id => $ip->id],
        ]);
    }

    public function test_member_address_outside_the_range_is_rejected_when_adding_and_updating(): void
    {
        SSH::fake();
        $first = $this->readyServer('2001:db8::41');
        $firstIp = $this->privateIp($first, '10.50.0.5', 24);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'contained',
            'type' => 'custom',
            'cidr' => '10.50.0.0/24',
            'servers' => [$first->id],
            'ip_addresses' => [$first->id => $firstIp->id],
        ]);

        $second = $this->readyServer('2001:db8::42');
        $outside = $this->privateIp($second, '10.99.0.5', 24);

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
        $alsoOutside = $this->privateIp($first, '10.99.0.6', 24);

        $this->expectException(ValidationException::class);
        app(UpdateNetworkServerIp::class)->update($member, ['server_ip_address_id' => $alsoOutside->id]);
    }

    /**
     * A network with no declared range derives per-member host rules instead, so containment
     * cannot be checked and any private address is allowed.
     */
    public function test_network_without_a_range_accepts_any_private_address(): void
    {
        SSH::fake();
        $server = $this->readyServer('2001:db8::50');
        $ip = $this->privateIp($server, '10.77.0.5', 24);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'no-range',
            'type' => 'custom',
            'cidr' => '',
            'servers' => [$server->id],
            'ip_addresses' => [$server->id => $ip->id],
        ]);

        $this->assertNull($network->cidr);
        $this->assertSame(1, $network->servers()->count());
    }

    public function test_malformed_cidr_is_rejected_for_both_families(): void
    {
        SSH::fake();
        $server = $this->readyServer('2001:db8::60');
        $ip = $this->privateIp($server, '10.0.0.5', 24);

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
                $this->assertTrue(true);
            }
        }

        $this->assertSame(0, Network::query()->where('project_id', $this->server->project_id)->count());
    }
}
