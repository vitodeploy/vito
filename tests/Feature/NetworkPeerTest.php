<?php

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

uses(RefreshDatabase::class);

/**
 * @param  array<int, int>  $servers
 */
function vitoPestFeatureNetworkPeerTestWireguardNetwork(array $servers): Network
{
    return app(CreateNetwork::class)->create(test()->server->project, [
        'name' => 'wg-net',
        'type' => 'wireguard',
        'servers' => $servers,
    ]);
}

function vitoPestFeatureNetworkPeerTestReadyPeerServer(): Server
{
    return Server::factory()->create([
        'project_id' => test()->server->project_id,
        'user_id' => test()->user->id,
        'status' => ServerStatus::READY,
    ]);
}

test('peer on a member less network activates once a server joins', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);

    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
        'type' => NetworkType::WIREGUARD,
    ]);

    app(RecomputeNetworkStatus::class)->handle($network);

    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);
    expect($peer->fresh()?->status)->toBe(NetworkPeerStatus::PENDING);

    app(AddServersToNetwork::class)->add($network, ['servers' => [$this->server->id]]);

    expect($peer->fresh()?->status)->toBe(NetworkPeerStatus::ACTIVE);
});

test('create peer allocates next free ip', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);

    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    $this->assertDatabaseHas('network_peers', [
        'id' => $peer->id,
        'network_id' => $network->id,
        'ip' => '100.64.0.3',
        'byo' => false,
    ]);
    expect($peer->private_key)->not->toBeNull();
});

test('server add skips peer ip', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);

    app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    $other = vitoPestFeatureNetworkPeerTestReadyPeerServer();
    app(AddServersToNetwork::class)->add($network, ['servers' => [$other->id]]);

    $this->assertDatabaseHas('network_servers', [
        'network_id' => $network->id,
        'server_id' => $other->id,
        'ip' => '100.64.0.4',
    ]);
});

/**
 * @return array<string, array{0: NetworkType}>
 */
dataset('nonWireGuardTypes', function () {
    return [
        'custom' => [NetworkType::CUSTOM],
        'provider' => [NetworkType::PROVIDER],
    ];
});

test('peer routes 404 on non wireguard network', function (NetworkType $type) {
    $network = Network::factory()->create([
        'project_id' => $this->server->project_id,
        'type' => $type,
    ]);

    $this->actingAs($this->user);

    $this->get(route('networks.peers', $network))->assertNotFound();
    $this->post(route('networks.peers.store', $network), ['name' => 'laptop'])->assertNotFound();
})->with('nonWireGuardTypes');

test('peer appears in member config without endpoint', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);

    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    $conf = SSH::getUploadedContent();
    $this->assertStringContainsString($peer->public_key, $conf);
    $this->assertStringNotContainsString('Endpoint', $conf);
});

test('devices handshake rule is materialized and removed', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);

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
});

test('peer config is valid wireguard ini format', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    $config = app(GetNetworkPeerConfig::class)->config($peer)['config'];

    expect($config)->toMatch('/^\[Interface\]$/m');
    expect($config)->toMatch('/^Address = '.preg_quote((string) $peer->ip, '/').'\/32$/m');
    expect($config)->toMatch('/^PrivateKey = \S+$/m');
    expect($config)->toMatch('/^\[Peer\]$/m');
    expect($config)->toMatch('/^PublicKey = \S+$/m');
    expect($config)->toMatch('/^AllowedIPs = \S+$/m');
    expect($config)->toMatch('/^PersistentKeepalive = 25$/m');
});

test('private key is revealed once but config remains available', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
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
});

test('config reflects servers added after the key was concealed', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    app(ConcealNetworkPeerKey::class)->conceal($peer);

    $newServer = vitoPestFeatureNetworkPeerTestReadyPeerServer();
    app(AddServersToNetwork::class)->add($network, ['servers' => [$newServer->id]]);

    $config = app(GetNetworkPeerConfig::class)->config($peer->fresh());

    expect($config['private_key'])->toBeNull();
    $this->assertStringContainsString(
        (string) $newServer->ip,
        $config['config'],
        'A peer must be able to regenerate its config to reach servers added after concealment.'
    );
});

test('peer config routes the subnet through a stable member', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $second = vitoPestFeatureNetworkPeerTestReadyPeerServer();
    $third = vitoPestFeatureNetworkPeerTestReadyPeerServer();

    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
    app(AddServersToNetwork::class)->add($network, ['servers' => [$second->id, $third->id]]);

    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    $lowest = $network->servers()->orderBy('id')->firstOrFail();

    for ($i = 0; $i < 3; $i++) {
        $config = app(GetNetworkPeerConfig::class)->config($peer->refresh())['config'];

        $blocks = array_slice(preg_split('/^\[Peer\]$/m', $config) ?: [], 1);
        expect($blocks)->toHaveCount(3);

        $this->assertStringContainsString('AllowedIPs = '.$network->cidr, $blocks[0]);
        $this->assertStringContainsString('PublicKey = '.$lowest->public_key, $blocks[0]);

        foreach (array_slice($blocks, 1) as $block) {
            $this->assertStringNotContainsString('AllowedIPs = '.$network->cidr, $block);
            expect($block)->toMatch('/AllowedIPs = \S+\/32/');
        }
    }
});

test('byo peer keeps config and rejects conceal', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);

    $key = base64_encode(random_bytes(32));
    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop', 'public_key' => $key]);

    $this->assertDatabaseHas('network_peers', ['id' => $peer->id, 'byo' => true, 'private_key' => null, 'public_key' => $key]);

    $this->actingAs($this->user);

    $this->getJson(route('networks.peers.config', ['network' => $network->id, 'networkPeer' => $peer->id]))
        ->assertOk()
        ->assertJsonPath('config', fn (string $config): bool => str_contains($config, 'REPLACE_WITH_YOUR_PRIVATE_KEY'));

    $this->post(route('networks.peers.conceal', ['network' => $network->id, 'networkPeer' => $peer->id]))
        ->assertSessionHasErrors('peer');
});

test('byo public key validation', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);

    $this->actingAs($this->user);

    $this->post(route('networks.peers.store', $network), ['name' => 'bad', 'public_key' => 'not-base64!!'])
        ->assertSessionHasErrors('public_key');

    $memberKey = $network->servers()->firstOrFail()->public_key;
    $this->post(route('networks.peers.store', $network), ['name' => 'collide-member', 'public_key' => $memberKey])
        ->assertSessionHasErrors('public_key');

    $existing = base64_encode(random_bytes(32));
    app(CreateNetworkPeer::class)->create($network, ['name' => 'first', 'public_key' => $existing]);
    $this->post(route('networks.peers.store', $network), ['name' => 'collide-peer', 'public_key' => $existing])
        ->assertSessionHasErrors('public_key');
});

/**
 * @return array<string, array{0: string}>
 */
dataset('untrustedPublicKeys', function () {
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
});

test('untrusted byo public keys are rejected', function (string $publicKey) {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);

    $this->actingAs($this->user);

    $this->post(route('networks.peers.store', $network), ['name' => 'laptop', 'public_key' => $publicKey])
        ->assertSessionHasErrors('public_key');

    $this->assertDatabaseMissing('network_peers', ['network_id' => $network->id, 'public_key' => $publicKey]);
})->with('untrustedPublicKeys');

test('regenerate peer keys', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);
    $original = $peer->public_key;

    $this->actingAs($this->user);
    $this->post(route('networks.peers.regenerate', ['network' => $network->id, 'networkPeer' => $peer->id]))
        ->assertRedirect();

    $peer->refresh();
    $this->assertNotSame($original, $peer->public_key);
    expect($peer->byo)->toBeFalse();
    expect($peer->hasPrivateKey())->toBeTrue();
});

test('disable removes peer from config and enable restores', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    app(UpdateNetworkPeer::class)->update($peer, ['name' => 'laptop', 'enabled' => false]);
    expect($peer->refresh()->status)->toBe(NetworkPeerStatus::DISABLED);
    $this->assertStringNotContainsString($peer->public_key, SSH::getUploadedContent());

    app(UpdateNetworkPeer::class)->update($peer, ['name' => 'laptop', 'enabled' => true]);
    $this->assertStringContainsString($peer->public_key, SSH::getUploadedContent());
});

test('poll peer handshakes falls back to a later eligible member', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $second = vitoPestFeatureNetworkPeerTestReadyPeerServer();

    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
    app(AddServersToNetwork::class)->add($network, ['servers' => [$second->id]]);
    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    $first = $network->servers()->where('server_id', $this->server->id)->firstOrFail();
    $first->server->service(WireGuard::type())->update(['status' => ServiceStatus::INSTALLING]);

    SSH::fake("{$peer->public_key}\t1700000000");
    (new PollPeerHandshakesJob($network->refresh()))->handle();

    expect($peer->refresh()->last_handshake_at)->not->toBeNull();
});

test('poll peer handshakes updates last handshake', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
    $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    SSH::fake("{$peer->public_key}\t1700000000");
    (new PollPeerHandshakesJob($network->refresh()))->handle();
    expect($peer->refresh()->last_handshake_at)->not->toBeNull();

    $peer->update(['last_handshake_at' => null]);
    SSH::fake("{$peer->public_key}\t0");
    (new PollPeerHandshakesJob($network->refresh()))->handle();
    expect($peer->refresh()->last_handshake_at)->toBeNull();
});

test('duplicate name is rejected', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
    app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

    $this->actingAs($this->user);
    $this->post(route('networks.peers.store', $network), ['name' => 'laptop'])
        ->assertSessionHasErrors('name');
});

test('config endpoint is write gated', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);
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
});

test('peer from another project is not found', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);

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
});

test('recompute activates pending peers', function () {
    SSH::fake();
    $this->server->update(['status' => ServerStatus::READY]);
    $network = vitoPestFeatureNetworkPeerTestWireguardNetwork([$this->server->id]);

    $peer = NetworkPeer::factory()->create([
        'network_id' => $network->id,
        'status' => NetworkPeerStatus::PENDING,
    ]);

    app(RecomputeNetworkStatus::class)->handle($network);

    expect($peer->refresh()->status)->toBe(NetworkPeerStatus::ACTIVE);
});
