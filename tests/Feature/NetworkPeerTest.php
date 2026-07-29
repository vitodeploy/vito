<?php

namespace Tests\Feature;

use App\Actions\Network\AddServersToNetwork;
use App\Actions\Network\ConcealNetworkPeerKey;
use App\Actions\Network\CreateNetwork;
use App\Actions\Network\CreateNetworkPeer;
use App\Actions\Network\DeleteNetworkPeer;
use App\Actions\Network\GetNetworkPeerConfig;
use App\Actions\Network\RecomputeNetworkStatus;
use App\Actions\Network\UpdateNetworkPeer;
use App\Enums\NetworkPeerStatus;
use App\Enums\NetworkType;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Jobs\Network\PollPeerHandshakesJob;
use App\Models\Network;
use App\Models\NetworkPeer;
use App\Models\Server;
use App\Models\User;
use App\Services\VPN\WireGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NetworkPeerTest extends TestCase
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

    private function readyPeerServer(): Server
    {
        return Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);
    }

    /**
     * A network with no members has nothing to configure the peer on, so the peer waits rather
     * than claiming to be connected. It must clear itself once a server joins — nothing else
     * activates a pending peer.
     */
    public function test_peer_on_a_member_less_network_activates_once_a_server_joins(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = Network::factory()->create([
            'project_id' => $this->server->project_id,
            'type' => NetworkType::WIREGUARD,
        ]);

        app(RecomputeNetworkStatus::class)->handle($network);

        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);
        $this->assertSame(NetworkPeerStatus::PENDING, $peer->fresh()?->status);

        app(AddServersToNetwork::class)->add($network, ['servers' => [$this->server->id]]);

        $this->assertSame(NetworkPeerStatus::ACTIVE, $peer->fresh()?->status);
    }

    public function test_create_peer_allocates_next_free_ip(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);

        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $this->assertDatabaseHas('network_peers', [
            'id' => $peer->id,
            'network_id' => $network->id,
            'ip' => '100.64.0.3',
            'byo' => false,
        ]);
        $this->assertNotNull($peer->private_key);
    }

    public function test_server_add_skips_peer_ip(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);

        app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $other = $this->readyPeerServer();
        app(AddServersToNetwork::class)->add($network, ['servers' => [$other->id]]);

        $this->assertDatabaseHas('network_servers', [
            'network_id' => $network->id,
            'server_id' => $other->id,
            'ip' => '100.64.0.4',
        ]);
    }

    /**
     * @return array<string, array{0: NetworkType}>
     */
    public static function nonWireGuardTypes(): array
    {
        return [
            'custom' => [NetworkType::CUSTOM],
            'provider' => [NetworkType::PROVIDER],
        ];
    }

    #[DataProvider('nonWireGuardTypes')]
    public function test_peer_routes_404_on_non_wireguard_network(NetworkType $type): void
    {
        $network = Network::factory()->create([
            'project_id' => $this->server->project_id,
            'type' => $type,
        ]);

        $this->actingAs($this->user);

        $this->get(route('networks.peers', $network))->assertNotFound();
        $this->post(route('networks.peers.store', $network), ['name' => 'laptop'])->assertNotFound();
    }

    public function test_peer_appears_in_member_config_without_endpoint(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);

        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $conf = SSH::getUploadedContent();
        $this->assertStringContainsString($peer->public_key, $conf);
        $this->assertStringNotContainsString('Endpoint', $conf);
    }

    public function test_devices_handshake_rule_is_materialized_and_removed(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);

        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        SSH::assertExecutedContains('allow from any to any proto udp port 51820');
        $this->assertDatabaseHas('server_network_rules', [
            'network_id' => $network->id,
            'name' => 'WireGuard handshake (devices)',
            'source' => null,
        ]);

        app(DeleteNetworkPeer::class)->delete($peer);

        $this->assertDatabaseMissing('server_network_rules', [
            'network_id' => $network->id,
            'name' => 'WireGuard handshake (devices)',
        ]);
    }

    /**
     * WireGuard requires one directive per line — a reformatted blade template that
     * collapses these onto a single line produces a config no client can parse.
     */
    public function test_peer_config_is_valid_wireguard_ini_format(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $config = app(GetNetworkPeerConfig::class)->config($peer)['config'];

        $this->assertMatchesRegularExpression('/^\[Interface\]$/m', $config);
        $this->assertMatchesRegularExpression('/^Address = '.preg_quote((string) $peer->ip, '/').'\/32$/m', $config);
        $this->assertMatchesRegularExpression('/^PrivateKey = \S+$/m', $config);
        $this->assertMatchesRegularExpression('/^\[Peer\]$/m', $config);
        $this->assertMatchesRegularExpression('/^PublicKey = \S+$/m', $config);
        $this->assertMatchesRegularExpression('/^AllowedIPs = \S+$/m', $config);
        $this->assertMatchesRegularExpression('/^PersistentKeepalive = 25$/m', $config);
    }

    /**
     * The config is not a secret — it must stay available so it can be regenerated.
     */
    public function test_private_key_is_revealed_once_but_config_remains_available(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $this->actingAs($this->user);

        $url = route('networks.peers.config', ['network' => $network->id, 'networkPeer' => $peer->id]);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('private_key', fn (?string $key): bool => filled($key))
            ->assertJsonPath('config', fn (string $config): bool => str_contains($config, '[Interface]')
                && ! str_contains($config, 'REPLACE_WITH_YOUR_PRIVATE_KEY'));

        $this->post(route('networks.peers.conceal', ['network' => $network->id, 'networkPeer' => $peer->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('network_peers', ['id' => $peer->id, 'private_key' => null]);

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('private_key', null)
            ->assertJsonPath('config', fn (string $config): bool => str_contains($config, 'REPLACE_WITH_YOUR_PRIVATE_KEY'));
    }

    public function test_config_reflects_servers_added_after_the_key_was_concealed(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        app(ConcealNetworkPeerKey::class)->conceal($peer);

        $newServer = $this->readyPeerServer();
        app(AddServersToNetwork::class)->add($network, ['servers' => [$newServer->id]]);

        $config = app(GetNetworkPeerConfig::class)->config($peer->fresh());

        $this->assertNull($config['private_key']);
        $this->assertStringContainsString(
            (string) $newServer->ip,
            $config['config'],
            'A peer must be able to regenerate its config to reach servers added after concealment.'
        );
    }

    /**
     * The first member listed in a peer config carries the whole network range as its
     * AllowedIPs, so it is the server the device routes the subnet through. That makes the
     * order load-bearing: it must be stable across regenerations, not left to the database.
     */
    public function test_peer_config_routes_the_subnet_through_a_stable_member(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $second = $this->readyPeerServer();
        $third = $this->readyPeerServer();

        $network = $this->wireguardNetwork([$this->server->id]);
        app(AddServersToNetwork::class)->add($network, ['servers' => [$second->id, $third->id]]);

        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $lowest = $network->servers()->orderBy('id')->firstOrFail();

        for ($i = 0; $i < 3; $i++) {
            $config = app(GetNetworkPeerConfig::class)->config($peer->refresh())['config'];

            $blocks = array_slice(preg_split('/^\[Peer\]$/m', $config) ?: [], 1);
            $this->assertCount(3, $blocks);

            $this->assertStringContainsString('AllowedIPs = '.$network->cidr, $blocks[0]);
            $this->assertStringContainsString('PublicKey = '.$lowest->public_key, $blocks[0]);

            foreach (array_slice($blocks, 1) as $block) {
                $this->assertStringNotContainsString('AllowedIPs = '.$network->cidr, $block);
                $this->assertMatchesRegularExpression('/AllowedIPs = \S+\/32/', $block);
            }
        }
    }

    public function test_byo_peer_keeps_config_and_rejects_conceal(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);

        $key = base64_encode(random_bytes(32));
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop', 'public_key' => $key]);

        $this->assertDatabaseHas('network_peers', ['id' => $peer->id, 'byo' => true, 'private_key' => null, 'public_key' => $key]);

        $this->actingAs($this->user);

        $this->getJson(route('networks.peers.config', ['network' => $network->id, 'networkPeer' => $peer->id]))
            ->assertOk()
            ->assertJsonPath('config', fn (string $config): bool => str_contains($config, 'REPLACE_WITH_YOUR_PRIVATE_KEY'));

        $this->post(route('networks.peers.conceal', ['network' => $network->id, 'networkPeer' => $peer->id]))
            ->assertSessionHasErrors('peer');
    }

    public function test_byo_public_key_validation(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);

        $this->actingAs($this->user);

        $this->post(route('networks.peers.store', $network), ['name' => 'bad', 'public_key' => 'not-base64!!'])
            ->assertSessionHasErrors('public_key');

        $memberKey = $network->servers()->first()->public_key;
        $this->post(route('networks.peers.store', $network), ['name' => 'collide-member', 'public_key' => $memberKey])
            ->assertSessionHasErrors('public_key');

        $existing = base64_encode(random_bytes(32));
        app(CreateNetworkPeer::class)->create($network, ['name' => 'first', 'public_key' => $existing]);
        $this->post(route('networks.peers.store', $network), ['name' => 'collide-peer', 'public_key' => $existing])
            ->assertSessionHasErrors('public_key');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function untrustedPublicKeys(): array
    {
        $valid = 'QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVphYmNkZWY=';

        return [
            'embedded space' => ['QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVphYmNkZW Y='],
            'embedded newline' => ["QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVphYmNkZW\nY="],
            'trailing newline directive' => [$valid."\nAllowedIPs = 0.0.0.0/0"],
            'embedded tab' => ["QUJDREVGR0hJSktMTU5PUFFSU1RVVldYWVphYmNkZW\tY="],
            'command substitution' => ['$(id)'],
            'command chaining' => [$valid.'; rm -rf /'],
            'backticks' => ['`whoami`'],
            'too short' => ['QUJDREVG'],
            'wrong padding' => [str_repeat('A', 44)],
        ];
    }

    /**
     * A bring-your-own public key is interpolated straight into the WireGuard config
     * template, so anything but the exact 44-character wire form must be rejected.
     */
    #[DataProvider('untrustedPublicKeys')]
    public function test_untrusted_byo_public_keys_are_rejected(string $publicKey): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);

        $this->actingAs($this->user);

        $this->post(route('networks.peers.store', $network), ['name' => 'laptop', 'public_key' => $publicKey])
            ->assertSessionHasErrors('public_key');

        $this->assertDatabaseMissing('network_peers', ['network_id' => $network->id, 'public_key' => $publicKey]);
    }

    public function test_regenerate_peer_keys(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);
        $original = $peer->public_key;

        $this->actingAs($this->user);
        $this->post(route('networks.peers.regenerate', ['network' => $network->id, 'networkPeer' => $peer->id]))
            ->assertRedirect();

        $peer->refresh();
        $this->assertNotSame($original, $peer->public_key);
        $this->assertFalse($peer->byo);
        $this->assertTrue($peer->hasPrivateKey());
    }

    public function test_disable_removes_peer_from_config_and_enable_restores(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        app(UpdateNetworkPeer::class)->update($peer, ['name' => 'laptop', 'enabled' => false]);
        $this->assertSame(NetworkPeerStatus::DISABLED, $peer->refresh()->status);
        $this->assertStringNotContainsString($peer->public_key, SSH::getUploadedContent());

        app(UpdateNetworkPeer::class)->update($peer, ['name' => 'laptop', 'enabled' => true]);
        $this->assertStringContainsString($peer->public_key, SSH::getUploadedContent());
    }

    /**
     * The lowest-id member is polled first, but a member whose WireGuard service is not yet
     * READY must not stop the poll — a later member with a ready service still serves it.
     */
    public function test_poll_peer_handshakes_falls_back_to_a_later_eligible_member(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $second = $this->readyPeerServer();

        $network = $this->wireguardNetwork([$this->server->id]);
        app(AddServersToNetwork::class)->add($network, ['servers' => [$second->id]]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $first = $network->servers()->where('server_id', $this->server->id)->firstOrFail();
        $first->server->service(WireGuard::type())->update(['status' => ServiceStatus::INSTALLING]);

        SSH::fake("{$peer->public_key}\t1700000000");
        (new PollPeerHandshakesJob($network->refresh()))->handle();

        $this->assertNotNull($peer->refresh()->last_handshake_at);
    }

    public function test_poll_peer_handshakes_updates_last_handshake(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        SSH::fake("{$peer->public_key}\t1700000000");
        (new PollPeerHandshakesJob($network->refresh()))->handle();
        $this->assertNotNull($peer->refresh()->last_handshake_at);

        $peer->update(['last_handshake_at' => null]);
        SSH::fake("{$peer->public_key}\t0");
        (new PollPeerHandshakesJob($network->refresh()))->handle();
        $this->assertNull($peer->refresh()->last_handshake_at);
    }

    public function test_duplicate_name_is_rejected(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $this->actingAs($this->user);
        $this->post(route('networks.peers.store', $network), ['name' => 'laptop'])
            ->assertSessionHasErrors('name');
    }

    public function test_config_endpoint_is_write_gated(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $viewer = User::factory()->create();
        $this->server->project->users()->create(['user_id' => $viewer->id, 'role' => UserRole::USER]);
        $viewer->current_project_id = $this->server->project_id;
        $viewer->save();

        $this->actingAs($viewer);

        $this->get(route('networks.peers', $network))->assertOk();
        $this->getJson(route('networks.peers.config', ['network' => $network->id, 'networkPeer' => $peer->id]))
            ->assertForbidden();
        $this->post(route('networks.peers.store', $network), ['name' => 'nope'])->assertForbidden();
    }

    public function test_peer_from_another_project_is_not_found(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);

        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();
        $otherNetwork = Network::factory()->create([
            'project_id' => $otherUser->current_project_id,
            'type' => NetworkType::WIREGUARD,
        ]);
        $foreignPeer = NetworkPeer::factory()->create(['network_id' => $otherNetwork->id]);

        $this->actingAs($this->user);
        $this->getJson(route('networks.peers.config', ['network' => $network->id, 'networkPeer' => $foreignPeer->id]))
            ->assertNotFound();
    }

    public function test_recompute_activates_pending_peers(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);

        $peer = NetworkPeer::factory()->create([
            'network_id' => $network->id,
            'status' => NetworkPeerStatus::PENDING,
        ]);

        app(RecomputeNetworkStatus::class)->handle($network);

        $this->assertSame(NetworkPeerStatus::ACTIVE, $peer->refresh()->status);
    }
}
