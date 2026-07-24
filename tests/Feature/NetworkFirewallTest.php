<?php

namespace Tests\Feature;

use App\Actions\FirewallRule\ManageRule;
use App\Actions\Network\CreateNetwork;
use App\Actions\Network\ManageNetworkFirewallRule;
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
use Inertia\Testing\AssertableInertia;
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

    public function test_provider_network_firewall_uses_cidr_source(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $ip = ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'ip' => '10.0.0.5',
            'type' => IpAddressType::PRIVATE,
        ]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'prov-net',
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
    }

    public function test_provider_network_without_cidr_uses_member_private_ip(): void
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
            'name' => 'prov-net',
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

    public function test_provider_network_applies_catch_all_on_create(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $ip = ServerIpAddress::factory()->create(['server_id' => $this->server->id, 'ip' => '10.0.0.5', 'type' => IpAddressType::PRIVATE]);

        app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'prov-net',
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
}
