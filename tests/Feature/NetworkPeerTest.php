<?php

namespace Tests\Feature;

use App\Actions\Network\AddServersToNetwork;
use App\Actions\Network\CreateNetwork;
use App\Actions\Network\CreateNetworkPeer;
use App\Enums\NetworkPeerStatus;
use App\Enums\NetworkType;
use App\Enums\ServerStatus;
use App\Enums\UserRole;
use App\Facades\SSH;
use App\Jobs\Network\PollPeerHandshakesJob;
use App\Models\Network;
use App\Models\NetworkPeer;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_peer_routes_404_on_provider_network(): void
    {
        $network = Network::factory()->create([
            'project_id' => $this->server->project_id,
            'type' => NetworkType::PROVIDER,
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

        app(\App\Actions\Network\DeleteNetworkPeer::class)->delete($peer);

        $this->assertDatabaseMissing('server_network_rules', [
            'network_id' => $network->id,
            'name' => 'WireGuard handshake (devices)',
        ]);
    }

    public function test_managed_peer_one_time_reveal(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $this->actingAs($this->user);

        $this->getJson(route('networks.peers.config', ['network' => $network->id, 'networkPeer' => $peer->id]))
            ->assertOk()
            ->assertJsonPath('config', fn (string $config): bool => str_contains($config, '[Interface]'));

        $this->post(route('networks.peers.conceal', ['network' => $network->id, 'networkPeer' => $peer->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('network_peers', ['id' => $peer->id, 'private_key' => null]);

        $this->getJson(route('networks.peers.config', ['network' => $network->id, 'networkPeer' => $peer->id]))
            ->assertStatus(410);
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
        $this->assertTrue($peer->canShowConfig());
    }

    public function test_disable_removes_peer_from_config_and_enable_restores(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);
        $network = $this->wireguardNetwork([$this->server->id]);
        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        app(\App\Actions\Network\UpdateNetworkPeer::class)->update($peer, ['name' => 'laptop', 'enabled' => false]);
        $this->assertSame(NetworkPeerStatus::DISABLED, $peer->refresh()->status);
        $this->assertStringNotContainsString($peer->public_key, SSH::getUploadedContent());

        app(\App\Actions\Network\UpdateNetworkPeer::class)->update($peer, ['name' => 'laptop', 'enabled' => true]);
        $this->assertStringContainsString($peer->public_key, SSH::getUploadedContent());
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

        app(\App\Actions\Network\RecomputeNetworkStatus::class)->handle($network);

        $this->assertSame(NetworkPeerStatus::ACTIVE, $peer->refresh()->status);
    }
}
