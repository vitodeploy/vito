<?php

namespace Tests\Feature;

use App\Actions\FirewallRule\ManageRule;
use App\Actions\Network\CompileServerFirewallRules;
use App\Actions\Network\CreateNetwork;
use App\Actions\Network\ManageNetworkFirewallRule;
use App\Actions\Network\UpdateNetwork;
use App\Enums\IpAddressType;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\ServerStatus;
use App\Facades\SSH;
use App\Jobs\Network\ApplyNetworkFirewallJob;
use App\Models\Server;
use App\Models\ServerIpAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NetworkFirewallTest extends TestCase
{
    use RefreshDatabase;

    private function wireguardNetwork(array $servers, bool $firewall): \App\Models\Network
    {
        return app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'firewall_enabled' => $firewall,
            'servers' => $servers,
        ]);
    }

    public function test_wireguard_handshake_port_is_opened_even_when_firewall_disabled(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $peer = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $this->wireguardNetwork([$this->server->id, $peer->id], false);

        SSH::assertExecutedContains('proto udp port 51820');
    }

    public function test_firewall_enabled_emits_default_allow_catch_all(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $this->wireguardNetwork([$this->server->id], true);

        SSH::assertExecutedContains('allow from 100.64.0.0/24 to any');
    }

    public function test_deny_rule_is_emitted_scoped_to_the_network_cidr(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id], true);

        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'no-mysql',
            'type' => 'deny',
            'protocol' => 'tcp',
            'port' => '3306',
        ]);

        SSH::assertExecutedContains('deny from 100.64.0.0/24 to any proto tcp port 3306');
        $this->assertDatabaseHas('network_firewall_rules', [
            'network_id' => $network->id,
            'type' => 'deny',
            'port' => '3306',
        ]);
    }

    public function test_network_rules_are_not_stored_as_server_firewall_rules(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id], true);
        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'no-mysql',
            'type' => 'deny',
            'protocol' => 'tcp',
            'port' => '3306',
        ]);

        $this->assertSame(0, $this->server->firewallRules()->count());
    }

    public function test_server_firewall_page_exposes_managed_networks(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id], true);

        $this->actingAs($this->user);

        $this->get(route('firewall', ['server' => $this->server]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('firewall/index')
                ->where('managedNetworks.0.name', $network->name)
                ->where('managedNetworks.0.id', $network->id));
    }

    public function test_server_level_firewall_change_reapplies_network_rules(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $this->wireguardNetwork([$this->server->id], true);

        app(ManageRule::class)->create($this->server, [
            'name' => 'ssh',
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => '22',
        ]);

        SSH::assertExecutedContains('allow from 100.64.0.0/24 to any');
    }

    public function test_toggling_firewall_enabled_applies_network_rules(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id], false);

        app(UpdateNetwork::class)->update($network, ['firewall_enabled' => true]);

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

        $this->wireguardNetwork([$this->server->id, $peer->id], false);

        SSH::fake();
        $this->server->firewall()->handler()->install();

        SSH::assertExecutedContains('from '.$peer->ip.'/32 to any proto udp port 51820');
    }

    public function test_failed_firewall_apply_marks_member_failed(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id], true);
        $member = $network->servers()->firstOrFail();

        (new ApplyNetworkFirewallJob($member))->failed(new \RuntimeException('boom'));

        $this->assertSame(NetworkServerStatus::FAILED, $member->fresh()->status);
    }

    public function test_deny_rule_is_ordered_before_the_catch_all(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id], true);
        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'no-mysql',
            'type' => 'deny',
            'protocol' => 'tcp',
            'port' => '3306',
        ]);

        $specs = app(CompileServerFirewallRules::class)->forServer($this->server);

        $denyIndex = $this->indexOf($specs, fn ($s) => $s->type === 'deny' && $s->port === '3306');
        $catchAllIndex = $this->indexOf($specs, fn ($s) => $s->type === 'allow' && $s->port === null && $s->source === '100.64.0.0');

        $this->assertNotNull($denyIndex);
        $this->assertNotNull($catchAllIndex);
        $this->assertLessThan($catchAllIndex, $denyIndex);
    }

    public function test_portless_deny_all_rule_is_emitted(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id], true);
        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'lockdown',
            'type' => 'deny',
        ]);

        SSH::assertExecutedContains('deny from 100.64.0.0/24 to any');
    }

    public function test_disabling_firewall_removes_deny_rules_but_keeps_tunnel_allowed(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id], true);
        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'no-mysql',
            'type' => 'deny',
            'protocol' => 'tcp',
            'port' => '3306',
        ]);

        SSH::fake();
        app(UpdateNetwork::class)->update($network, ['firewall_enabled' => false]);

        SSH::assertNotExecutedContains('deny from 100.64.0.0/24 to any proto tcp port 3306');
        SSH::assertExecutedContains('allow from 100.64.0.0/24 to any');
    }

    public function test_firewall_change_on_offline_server_marks_member_pending(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = $this->wireguardNetwork([$this->server->id], true);

        $this->server->update(['status' => ServerStatus::DISCONNECTED]);

        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'no-mysql',
            'type' => 'deny',
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
            'type' => 'provider',
            'cidr' => '10.0.0.0/24',
            'firewall_enabled' => true,
            'servers' => [$this->server->id],
            'ip_addresses' => [$this->server->id => $ip->id],
        ]);

        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'no-mysql',
            'type' => 'deny',
            'protocol' => 'tcp',
            'port' => '3306',
        ]);

        SSH::assertExecutedContains('deny from 10.0.0.0/24 to any proto tcp port 3306');
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
            'type' => 'provider',
            'firewall_enabled' => true,
            'servers' => [$this->server->id, $peer->id],
            'ip_addresses' => [$this->server->id => $ip1->id, $peer->id => $ip2->id],
        ]);

        app(ManageNetworkFirewallRule::class)->create($network, [
            'name' => 'no-mysql',
            'type' => 'deny',
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
            'type' => 'provider',
            'cidr' => '10.0.0.0/24',
            'firewall_enabled' => true,
            'servers' => [$this->server->id],
            'ip_addresses' => [$this->server->id => $ip->id],
        ]);

        SSH::assertExecutedContains('allow from 10.0.0.0/24 to any');
    }

    public function test_wireguard_tunnel_is_allowed_when_firewall_disabled(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $this->wireguardNetwork([$this->server->id], false);

        SSH::assertExecutedContains('allow from 100.64.0.0/24 to any');
    }

    public function test_server_with_zero_networks_emits_no_network_rules(): void
    {
        $specs = app(CompileServerFirewallRules::class)->forServer($this->server);

        $this->assertSame([], $specs);
    }

    /**
     * @param  array<int, \stdClass>  $specs
     */
    private function indexOf(array $specs, callable $matcher): ?int
    {
        foreach ($specs as $index => $spec) {
            if ($matcher($spec)) {
                return $index;
            }
        }

        return null;
    }
}
