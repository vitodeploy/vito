<?php

namespace Tests\Feature;

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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NetworkFirewallTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, int>  $servers
     */
    private function wireguardNetwork(array $servers): Network
    {
        return app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => $servers,
        ]);
    }

    public function test_network_is_seeded_with_a_default_allow_all_rule(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id]);

        $this->assertDatabaseHas('network_firewall_rules', [
            'network_id' => $network->id,
            'name' => 'Allow all',
            'protocol' => null,
            'port' => null,
        ]);
    }

    public function test_wireguard_handshake_port_is_opened(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $peer = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $this->wireguardNetwork([$this->server->id, $peer->id]);

        SSH::assertExecutedContains('proto udp port 51820');
    }

    public function test_default_allow_all_rule_emits_catch_all(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $this->wireguardNetwork([$this->server->id]);

        SSH::assertExecutedContains('allow from 100.64.0.0/24 to any');
    }

    public function test_allow_rule_is_emitted_scoped_to_the_network_cidr(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id]);

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
    }

    public function test_network_rules_are_not_stored_as_server_firewall_rules(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id]);
        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'mysql',
            'protocol' => 'tcp',
            'port' => '3306',
        ]);

        $this->assertSame(0, $this->server->firewallRules()->count());
    }

    public function test_server_firewall_page_exposes_managed_networks(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id]);

        $this->actingAs($this->user);

        $this->get(route('firewall', ['server' => $this->server]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('firewall/index')
                ->where('networkRules.data.0.network_id', $network->id)
                ->where('networkRules.data.0.name', 'Allow all'));
    }

    public function test_leaving_member_network_is_not_listed_as_managed(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id]);
        $network->servers()->update(['status' => NetworkServerStatus::LEAVING]);

        $this->actingAs($this->user);

        $this->get(route('firewall', ['server' => $this->server]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('firewall/index')
                ->count('networkRules.data', 0));
    }

    public function test_server_level_firewall_change_reapplies_network_rules(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $this->wireguardNetwork([$this->server->id]);

        app(ManageRule::class)->create($this->server, [
            'name' => 'ssh',
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => '22',
        ]);

        SSH::assertExecutedContains('allow from 100.64.0.0/24 to any');
    }

    public function test_installing_ufw_opens_wireguard_handshake_for_existing_member(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $peer = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $this->wireguardNetwork([$this->server->id, $peer->id]);

        SSH::fake();
        $this->server->firewall()->handler()->install();

        SSH::assertExecutedContains('from '.$peer->ip.'/32 to any proto udp port 51820');
    }

    public function test_failed_firewall_apply_marks_member_failed(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id]);
        $member = $network->servers()->firstOrFail();

        (new ApplyNetworkFirewallJob($member))->failed(new \RuntimeException('boom'));

        $this->assertSame(NetworkServerStatus::FAILED, $member->fresh()->status);
    }

    public function test_deleting_allow_all_locks_down_but_keeps_tunnel_handshake(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $peer = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $network = $this->wireguardNetwork([$this->server->id, $peer->id]);

        $allowAll = $network->firewallRules()->whereNull('protocol')->whereNull('port')->firstOrFail();
        app(ManageNetworkFirewallRule::class)->delete($allowAll);

        $rules = ServerNetworkRule::query()->where('server_id', $this->server->id);

        $this->assertFalse((clone $rules)->where('kind', ServerNetworkRuleKind::RULE)->exists());
        $this->assertTrue((clone $rules)->where('kind', ServerNetworkRuleKind::HANDSHAKE)->exists());
    }

    public function test_firewall_change_on_offline_server_marks_member_pending(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id]);

        $this->server->update(['status' => ServerStatus::DISCONNECTED]);

        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'mysql',
            'protocol' => 'tcp',
            'port' => '3306',
        ]);

        $this->assertSame(NetworkServerStatus::PENDING, $network->servers()->firstOrFail()->status);
        $this->assertSame(NetworkStatus::SYNCING, $network->fresh()->status);
    }

    public function test_custom_network_with_a_cidr_uses_the_range_as_the_rule_source(): void
    {
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
    }

    public function test_custom_network_without_a_cidr_uses_each_member_private_ip(): void
    {
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
    }

    public function test_custom_network_applies_the_catch_all_rule_on_create(): void
    {
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
    }

    public function test_provider_network_never_uses_the_vpc_cidr_as_a_rule_source(): void
    {
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

        $this->assertContains('172.31.0.6', $sources);
        $this->assertNotContains('172.31.0.0', $sources, 'Provider networks must never widen the firewall to the whole VPC CIDR.');

        $this->assertSame(
            0,
            ServerNetworkRule::query()
                ->where('network_id', $network->id)
                ->where('mask', 16)
                ->count(),
            'Provider network rules must be per-member /32s.'
        );
    }

    public function test_server_with_zero_networks_has_no_materialized_rules(): void
    {
        $this->assertSame(0, ServerNetworkRule::query()->where('server_id', $this->server->id)->count());
    }

    public function test_network_create_materializes_handshake_and_catch_all_rows(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $peer = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $network = $this->wireguardNetwork([$this->server->id, $peer->id]);
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
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string, 2: string}>
     */
    public static function protocolAndPortCombinations(): array
    {
        return [
            'both' => ['tcp', '3306', 'to any proto tcp port 3306'],
            'port only' => [null, '3306', 'to any port 3306'],
            'protocol only' => ['udp', null, 'to any proto udp'],
            'neither' => [null, null, 'to any'],
        ];
    }

    /**
     * Protocol and port are documented as independently optional, so all four combinations
     * must validate and each must render a command `ufw` actually accepts — an empty `proto`
     * or a silently dropped protocol are both wrong.
     */
    #[DataProvider('protocolAndPortCombinations')]
    public function test_protocol_and_port_are_independently_optional(?string $protocol, ?string $port, string $expected): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $peer = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $network = $this->wireguardNetwork([$this->server->id, $peer->id]);
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
    }

    /**
     * `ufw` refuses a multi-port rule that names no protocol. Accepting one would apply on every
     * member and fail there, and because rules are reset and reapplied as a unit that failure
     * takes the network's other rules — and the server's own — down with it.
     */
    public function test_port_range_without_a_protocol_is_rejected(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id]);

        try {
            app(ManageNetworkFirewallRule::class)->create($network, [
                'name' => 'range',
                'protocol' => null,
                'port' => '3000:3010',
            ]);
            $this->fail('A port range without a protocol must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('protocol', $e->errors());
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
    }

    /**
     * Every network type derives rules that name the other members, so deleting a server must
     * clear its address from the remaining members' rules — a provider address in particular
     * can be reassigned to an unrelated host later.
     */
    public function test_deleting_a_server_clears_its_rules_from_remaining_members_of_a_custom_network(): void
    {
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
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function untrustedServerAddresses(): array
    {
        return [
            'command chaining' => ['10.0.0.9; sudo ufw disable'],
            'command substitution' => ['10.0.0.9 $(id)'],
            'backticks' => ['`whoami`'],
            'newline' => ["10.0.0.9\nsudo ufw disable"],
            'not an address' => ['not-an-ip'],
            'cidr not address' => ['10.0.0.0/24'],
        ];
    }

    /**
     * A server's public address becomes an unquoted `ufw` source on every WireGuard sibling,
     * and Blade escapes HTML rather than shell metacharacters — so it must be rejected at the
     * edge rather than reaching the template.
     */
    #[DataProvider('untrustedServerAddresses')]
    public function test_server_address_that_is_not_an_ip_is_rejected(string $ip): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $this->expectException(ValidationException::class);

        app(EditServer::class)->edit($this->server, [
            'name' => $this->server->name,
            'ip' => $ip,
            'port' => 22,
        ]);
    }

    public function test_untrusted_address_never_reaches_the_firewall_template(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $peer = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $network = $this->wireguardNetwork([$this->server->id, $peer->id]);

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
    }
}
