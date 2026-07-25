<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class NetworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_network_persists_and_casts_enums(): void
    {
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
        $this->assertInstanceOf(NetworkType::class, $network->type);
        $this->assertInstanceOf(NetworkStatus::class, $network->status);
        $this->assertTrue($network->port === 51820);
    }

    public function test_network_relations(): void
    {
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

        $this->assertTrue($network->servers->contains($member));
        $this->assertTrue($network->firewallRules->contains($rule));
        $this->assertTrue($this->server->project->networks->contains($network));
        $this->assertTrue($member->network->is($network));
        $this->assertTrue($member->server->is($this->server));
    }

    public function test_network_server_private_key_is_encrypted_at_rest(): void
    {
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
        $this->assertSame('super-secret-private-key', $member->fresh()->private_key);
        $this->assertInstanceOf(NetworkServerStatus::class, $member->fresh()->status);
    }

    public function test_generate_wireguard_keys_produce_valid_curve25519_format(): void
    {
        $keys = app(GenerateWireGuardKeys::class)->generate();

        foreach (['private_key', 'public_key'] as $key) {
            $decoded = base64_decode($keys[$key], true);
            $this->assertNotFalse($decoded);
            $this->assertSame(32, strlen((string) $decoded));
            $this->assertSame(44, strlen($keys[$key]));
        }

        $private = base64_decode($keys['private_key']);
        $this->assertSame(0, ord($private[0]) & 7);
        $this->assertSame(64, ord($private[31]) & 192);
    }

    public function test_wireguard_service_installs_and_uninstalls_via_ssh(): void
    {
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
        $this->assertSame('v1.0.20210914', $handler->version());

        $handler->uninstall();
        SSH::assertExecutedContains('apt-get remove -y wireguard');
    }

    public function test_wireguard_cannot_be_installed_via_services_store(): void
    {
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
    }

    public function test_wireguard_configure_network_uploads_conf_and_starts_interface(): void
    {
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
    }

    public function test_store_creates_wireguard_network_and_redirects(): void
    {
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
    }

    public function test_add_server_via_http_rejects_cross_project_server(): void
    {
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
    }

    public function test_destroy_network_via_http(): void
    {
        SSH::fake();
        $network = Network::factory()->create(['project_id' => $this->server->project_id]);

        $this->actingAs($this->user);

        $this->delete(route('networks.destroy', $network))->assertRedirect(route('networks'));
        $this->assertDatabaseMissing('networks', ['id' => $network->id]);
    }

    public function test_cannot_view_another_projects_network(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();
        $network = Network::factory()->create(['project_id' => $otherUser->current_project_id]);

        $this->actingAs($this->user);

        $this->get(route('networks.show', $network))->assertForbidden();
    }

    public function test_network_server_resource_never_exposes_private_key(): void
    {
        $network = Network::factory()->create(['project_id' => $this->server->project_id]);
        $member = NetworkServer::factory()->create([
            'network_id' => $network->id,
            'server_id' => $this->server->id,
            'ip' => '100.64.0.9',
            'private_key' => 'top-secret',
        ]);

        $data = (new NetworkServerResource($member->load('server')))->toArray(request());

        $this->assertArrayNotHasKey('private_key', $data);
        $this->assertArrayHasKey('public_key', $data);
    }

    public function test_network_peer_is_never_serialised_with_its_private_key(): void
    {
        $network = Network::factory()->create(['project_id' => $this->server->project_id]);
        $peer = NetworkPeer::factory()->create([
            'network_id' => $network->id,
            'private_key' => 'top-secret',
        ]);

        $this->assertArrayNotHasKey('private_key', (new NetworkPeerResource($peer))->toArray(request()));
        $this->assertArrayNotHasKey('private_key', $peer->toArray());
        $this->assertSame('top-secret', $peer->private_key);
    }

    public function test_firewall_rule_update_via_http(): void
    {
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
    }

    /**
     * The create dialog posts an empty string when the optional CIDR is left blank.
     */
    public function test_empty_cidr_is_stored_as_null_not_canonicalised(): void
    {
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
    }

    public function test_update_provider_network_server_ip_via_http(): void
    {
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
    }

    public function test_cannot_update_ip_on_wireguard_network_server(): void
    {
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
    }

    /**
     * Two WireGuard interfaces on one host cannot share a listen port. A server joining a second
     * network on the port it already runs moves that network to the next free one — refusing the
     * server instead would make "a server can belong to several networks" unreachable on the
     * default port, with no way to change it.
     */
    public function test_adding_a_server_moves_the_network_off_a_port_it_already_uses(): void
    {
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

        $this->assertSame(51820, $first->port);
        $this->assertSame(51820, $second->port);

        app(AddServersToNetwork::class)->add($second, ['servers' => [$this->server->id]]);

        $this->assertSame(51821, $second->fresh()?->port);
        $this->assertSame(51820, $first->fresh()?->port);
        $this->assertSame(2, $second->servers()->count());
    }

    /**
     * A peer's endpoint port is fixed when its config is downloaded, so a config already imported
     * on a laptop keeps pointing at the old port after the network moves. Nothing on the peer
     * changes and no handshake failure surfaces, so the move has to be called out.
     */
    public function test_port_move_warns_that_peers_must_download_their_config_again(): void
    {
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

        $this->assertSame(51821, $second->fresh()?->port);
    }

    /**
     * The declared range is what the firewall rules are scoped to, so a range wider than private
     * space would emit `allow from 0.0.0.0/0` on every member — opening those ports to the
     * internet on servers that were default-deny.
     */
    public function test_a_range_outside_private_address_space_is_rejected(): void
    {
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

        $this->assertSame(0, Network::query()->where('project_id', $this->server->project_id)->count());

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'private-range',
            'type' => 'custom',
            'cidr' => '10.80.0.0/24',
            'servers' => [$this->server->id],
            'ip_addresses' => [$this->server->id => $ip->id],
        ]);

        $this->assertSame('10.80.0.0/24', $network->cidr);
    }

    /**
     * The per-server IP rules are keyed by server id, so building them from raw input turned a
     * malformed `servers` entry into a 500 before the validator ever ran.
     */
    public function test_malformed_servers_input_is_a_validation_error(): void
    {
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
    }

    public function test_sub_resource_404_when_not_belonging_to_network(): void
    {
        $network = Network::factory()->create(['project_id' => $this->server->project_id]);
        $other = Network::factory()->create(['project_id' => $this->server->project_id]);
        $rule = NetworkFirewallRule::factory()->create(['network_id' => $other->id]);

        $this->actingAs($this->user);

        $this->delete(route('networks.firewall.destroy', ['network' => $network, 'networkFirewallRule' => $rule]))
            ->assertNotFound();
    }

    public function test_update_network_rename_via_http(): void
    {
        $network = Network::factory()->create(['project_id' => $this->server->project_id, 'name' => 'old-name']);

        $this->actingAs($this->user);

        $this->put(route('networks.update', $network), ['name' => 'new-name'])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('networks', ['id' => $network->id, 'name' => 'new-name']);
    }

    public function test_viewer_role_cannot_create_network(): void
    {
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
    }

    public function test_member_regenerate_via_http_resyncs_the_member(): void
    {
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
        $this->assertSame(NetworkServerStatus::ACTIVE, $fresh?->status);
        $this->assertSame(0, $fresh?->sync_attempts);
        SSH::assertExecutedContains('wg-quick@wg-vito-'.$network->id);
    }

    public function test_custom_network_duplicate_cidr_is_rejected(): void
    {
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
    }

    public function test_network_sync_logs_are_associated_to_the_network(): void
    {
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
        $this->assertSame(0, DB::table('server_logs')->whereNull('network_id')->count());
    }

    /**
     * `active` reads as "every server is configured and in sync", which a network with no
     * servers at all has no business claiming.
     */
    public function test_network_without_servers_is_not_reported_as_active(): void
    {
        $network = Network::factory()->create([
            'project_id' => $this->server->project_id,
            'status' => NetworkStatus::ACTIVE,
        ]);

        app(RecomputeNetworkStatus::class)->handle($network);

        $this->assertSame(NetworkStatus::CREATING, $network->fresh()?->status);
    }

    /**
     * The create dialog reads `servers` as a bare list of options, each carrying the private
     * addresses a custom network can be built from.
     */
    public function test_index_lists_servers_as_options_with_their_private_ips(): void
    {
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
    }

    /**
     * The servers tab of a custom network offers each member's other private addresses so the
     * one it joined with can be changed.
     */
    public function test_servers_page_lists_member_ips_for_a_custom_network(): void
    {
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
    }

    public function test_overview_returns_stats_and_recent_logs(): void
    {
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
    }

    public function test_logs_page_returns_network_logs_with_their_server(): void
    {
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
    }

    public function test_logs_page_is_not_visible_to_other_projects(): void
    {
        $network = Network::factory()->create([
            'project_id' => $this->server->project_id,
            'type' => NetworkType::CUSTOM,
        ]);

        $outsider = User::factory()->create();
        $outsider->ensureHasDefaultProject();

        $this->actingAs($outsider)
            ->get(route('networks.logs', $network))
            ->assertForbidden();
    }
}
