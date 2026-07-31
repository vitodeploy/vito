<?php

use App\Actions\Network\RecomputeNetworkStatus;
use App\Actions\Network\SyncProviderNetworks;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
use App\Enums\ServerStatus;
use App\Enums\UserRole;
use App\Events\SocketEvent;
use App\Exceptions\PrivateNetworkPersistError;
use App\Exceptions\PrivateNetworkSyncError;
use App\Facades\SSH;
use App\Jobs\Network\SyncProviderNetworksJob;
use App\Models\Network;
use App\Models\Server;
use App\Models\ServerNetworkRule;
use App\Models\ServerProvider;
use App\Models\User;
use App\ServerProviders\AWS;
use App\ServerProviders\Hetzner;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * `Http::fake()` merges stubs rather than replacing them, so a per-call fake would let
 * the first registration win for every later sync. One closure reading mutable state
 * is the only way to vary the response across runs within a test.
 */
beforeEach(function () {
    $this->networksPayload = [];
    $this->serversPayload = [];
    $this->failStatus = null;
    $this->malformed = false;

    SSH::fake();

    Http::fake(function (Request $request): PromiseInterface {
        if ($this->failStatus !== null) {
            return Http::response(['error' => ['message' => 'nope']], $this->failStatus);
        }

        if ($this->malformed) {
            return Http::response(['unexpected' => 'shape']);
        }

        if (str_contains($request->url(), '/networks')) {
            return Http::response([
                'networks' => $this->networksPayload,
                'meta' => ['pagination' => ['next_page' => null]],
            ]);
        }

        return Http::response([
            'servers' => $this->serversPayload,
            'meta' => ['pagination' => ['next_page' => null]],
        ]);
    });

    $this->connection = ServerProvider::factory()->create([
        'user_id' => $this->user->id,
        'provider' => Hetzner::id(),
        'profile' => 'hetzner-main',
        'credentials' => ['token' => 'secret-token'],
    ]);

    $this->server->update([
        'status' => ServerStatus::READY,
        'provider_id' => $this->connection->id,
        'provider_data' => ['hetzner_id' => 101, 'region' => 'nbg1'],
    ]);
});

function vitoPestFeatureNetworkProviderSyncTestOtherServer(int $hetznerId = 102): Server
{
    return Server::factory()->create([
        'project_id' => test()->server->project_id,
        'user_id' => test()->user->id,
        'status' => ServerStatus::READY,
        'provider_id' => test()->connection->id,
        'provider_data' => ['hetzner_id' => $hetznerId, 'region' => 'nbg1'],
    ]);
}

/**
 * @param  array<int, array<string, mixed>>  $networks
 * @param  array<int, array<string, mixed>>  $servers
 */
function vitoPestFeatureNetworkProviderSyncTestFakeProvider(array $networks, array $servers): void
{
    test()->networksPayload = $networks;
    test()->serversPayload = $servers;
    test()->failStatus = null;
}

function vitoPestFeatureNetworkProviderSyncTestSync(?Network $only = null): void
{
    app(SyncProviderNetworks::class)->forProject(test()->server->project, $only);
}

test('creates network and members from provider', function () {
    $peer = vitoPestFeatureNetworkProviderSyncTestOtherServer();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([[
        'id' => 4711,
        'name' => 'prod',
        'ip_range' => '10.0.0.0/16',
        'subnets' => [['network_zone' => 'eu-central']],
        'servers' => [101, 102],
    ]], [
        ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
        ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
    ]);

    vitoPestFeatureNetworkProviderSyncTestSync();

    $this->assertDatabaseHas('networks', [
        'project_id' => $this->server->project_id,
        'server_provider_id' => $this->connection->id,
        'external_id' => '4711',
        'name' => 'prod',
        'type' => NetworkType::PROVIDER->value,
        'cidr' => '10.0.0.0/16',
        'region' => 'eu-central',
    ]);

    $network = Network::query()->where('external_id', '4711')->firstOrFail();

    $this->assertDatabaseHas('network_servers', [
        'network_id' => $network->id,
        'server_id' => $this->server->id,
        'ip' => '10.0.0.2',
    ]);
    $this->assertDatabaseHas('network_servers', [
        'network_id' => $network->id,
        'server_id' => $peer->id,
        'ip' => '10.0.0.3',
    ]);
});

test('second run creates no duplicates', function () {
    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);

    vitoPestFeatureNetworkProviderSyncTestSync();
    vitoPestFeatureNetworkProviderSyncTestSync();

    expect(Network::query()->where('external_id', '4711')->count())->toBe(1);
    expect(Network::query()->where('type', NetworkType::PROVIDER)->count())->toBe(1);
});

test('adds and removes members as provider changes', function () {
    $peer = vitoPestFeatureNetworkProviderSyncTestOtherServer();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]], [
        ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
        ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
    ]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();
    expect($network->servers()->count())->toBe(2);

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    expect($network->servers()->where('server_id', $peer->id)->whereNot('status', NetworkServerStatus::LEAVING)->count())->toBe(0, 'A detached server must no longer be a live member.');
});

test('reattached server revives a leaving member instead of throwing', function () {
    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();
    $member = $network->servers()->firstOrFail();
    $member->update(['status' => NetworkServerStatus::LEAVING, 'sync_attempts' => 3]);

    vitoPestFeatureNetworkProviderSyncTestSync();

    $member->refresh();
    expect($member->status)->toBe(NetworkServerStatus::ACTIVE);
    expect($member->sync_attempts)->toBe(0);
    expect($network->servers()->count())->toBe(1, 'Reviving must not insert a second member row.');
});

test('already leaving member is not torn down again', function () {
    $peer = vitoPestFeatureNetworkProviderSyncTestOtherServer();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]], [
        ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
        ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
    ]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();
    $member = $network->servers()->where('server_id', $this->server->id)->firstOrFail();
    $member->update(['status' => NetworkServerStatus::LEAVING, 'sync_attempts' => 3]);

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [102]]], [['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $member->refresh();
    expect($member->sync_attempts)->toBe(3, 'Re-driving teardown would reset sync_attempts and defeat forceConverge escalation.');
    expect($peer->id)->not->toBeNull();
});

test('resurrects a deleting network that reappears', function () {
    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();
    $network->update(['status' => NetworkStatus::DELETING]);
    $network->servers()->update(['status' => NetworkServerStatus::LEAVING]);

    vitoPestFeatureNetworkProviderSyncTestSync();

    $network->refresh();
    $this->assertNotSame(NetworkStatus::DELETING, $network->status);
    expect($network->servers()->where('status', NetworkServerStatus::LEAVING)->count())->toBe(0, 'Resurrected networks must not keep LEAVING members that never converge.');
});

test('deletes network when vpc disappears', function () {
    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([], []);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $this->assertNotSame(
        NetworkStatus::ACTIVE,
        $network->fresh()?->status,
        'A removed VPC must start teardown.'
    );
});

test('provider failure never prunes and is rethrown', function () {
    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();

    $this->failStatus = 500;

    $threw = false;
    try {
        vitoPestFeatureNetworkProviderSyncTestSync();
    } catch (PrivateNetworkSyncError) {
        $threw = true;
    }

    expect($threw)->toBeTrue('A provider failure must not be reported as a successful sync.');

    $network->refresh();
    expect($network->status)->toBe(NetworkStatus::ACTIVE);
    expect($network->servers()->count())->toBe(1);
});

test('provider network is pruned after its last managed server is deleted', function () {
    $other = vitoPestFeatureNetworkProviderSyncTestOtherServer();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]], [
        ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
        ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
    ]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();
    expect($network->servers()->count())->toBe(2);

    $other->delete();
    $this->server->delete();

    expect($network->servers()->count())->toBe(0);

    vitoPestFeatureNetworkProviderSyncTestSync();

    expect(Network::query()->find($network->id))->toBeNull('A provider network with no managed servers left must be pruned, not stranded.');
});

test('network with live members survives when no instance ids can be queried', function () {
    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();
    expect($network->servers()->count())->toBe(1);

    $this->server->update(['provider_data' => ['region' => 'nbg1']]);

    vitoPestFeatureNetworkProviderSyncTestSync();

    expect(Network::query()->find($network->id))->not->toBeNull('A network with live members must not be pruned when the provider could not be queried.');
    expect($network->servers()->count())->toBe(1);
});

test('aws networks survive when no region is known', function () {
    $connection = vitoPestFeatureNetworkProviderSyncTestAwsConnection();
    $server = vitoPestFeatureNetworkProviderSyncTestAwsServer($connection, 'i-0abc');
    $network = vitoPestFeatureNetworkProviderSyncTestAwsNetwork($connection, 'vpc-0abc', $server);

    vitoPestFeatureNetworkProviderSyncTestSync();

    expect(Network::query()->find($network->id))->not->toBeNull('An AWS network must not be pruned when no region was available to query.');
    expect($network->servers()->count())->toBe(1);
});

test('aws networks survive when one server has no region', function () {
    $connection = vitoPestFeatureNetworkProviderSyncTestAwsConnection();
    vitoPestFeatureNetworkProviderSyncTestAwsServer($connection, 'i-known', 'eu-west-1');
    $stranded = vitoPestFeatureNetworkProviderSyncTestAwsServer($connection, 'i-unknown');
    $network = vitoPestFeatureNetworkProviderSyncTestAwsNetwork($connection, 'vpc-unknown', $stranded);

    vitoPestFeatureNetworkProviderSyncTestSync();

    expect(Network::query()->find($network->id))->not->toBeNull('A network in an unqueried region must not be pruned because other servers answered.');
    expect($network->servers()->count())->toBe(1);
});

test('provider network no member can be identified by becomes deletable', function () {
    $network = vitoPestFeatureNetworkProviderSyncTestProviderNetwork($this->connection->id);
    $network->servers()->create([
        'server_id' => $this->server->id,
        'ip' => '10.0.0.2',
        'status' => NetworkServerStatus::ACTIVE,
    ]);

    $this->actingAs($this->user);

    $this->delete(route('networks.destroy', $network))->assertForbidden();

    $this->server->update(['provider_data' => ['region' => 'nbg1']]);

    vitoPestFeatureNetworkProviderSyncTestSync();

    expect(Network::query()->find($network->id))->not->toBeNull('Sync cannot ask about this network, so it must not be pruned either.');

    $this->delete(route('networks.destroy', $network))->assertRedirect();
    $this->assertNotSame(NetworkStatus::ACTIVE, $network->fresh()?->status);
});

test('malformed provider response fails the sweep instead of pruning', function () {
    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();

    $this->malformed = true;

    expect(fn () => vitoPestFeatureNetworkProviderSyncTestSync())
        ->toThrow(PrivateNetworkSyncError::class);

    expect(Network::query()->find($network->id))->not->toBeNull('A network must not be pruned on the strength of an unreadable response.');
});

test('a network that cannot be persisted fails the sweep', function () {
    $peer = vitoPestFeatureNetworkProviderSyncTestOtherServer();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]], [
        ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
        ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
    ]);

    expect(fn () => vitoPestFeatureNetworkProviderSyncTestSync())
        ->toThrow(PrivateNetworkPersistError::class);

    expect($peer)->not->toBeNull();
});

test('status broadcast carries the provider relation', function () {
    Event::fake([SocketEvent::class]);

    $network = vitoPestFeatureNetworkProviderSyncTestProviderNetwork($this->connection->id);

    app(RecomputeNetworkStatus::class)->handle($network);

    $payload = null;

    Event::assertDispatched(SocketEvent::class, function (SocketEvent $event) use (&$payload): bool {
        if ($event->data->type === 'network.updated' && is_array($event->data->data)) {
            $payload = $event->data->data;
        }

        return true;
    });

    expect($payload)->toBeArray();
    expect($payload['provider'] ?? null)->toBe(Hetzner::id());
});

test('failed job does not log an unexpected exception message', function () {
    Log::spy();
    Notification::fake();

    (new SyncProviderNetworksJob($this->server->project))->failed(new RuntimeException('token=super-secret'));

    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $message, array $context): bool => $context['exception'] === RuntimeException::class
            && $context['reason'] === null
    );
});

function vitoPestFeatureNetworkProviderSyncTestAwsConnection(): ServerProvider
{
    return ServerProvider::factory()->create([
        'user_id' => test()->user->id,
        'provider' => AWS::id(),
        'profile' => 'aws-main',
        'credentials' => ['key' => 'key', 'secret' => 'secret'],
    ]);
}

function vitoPestFeatureNetworkProviderSyncTestAwsServer(ServerProvider $connection, string $instanceId, ?string $region = null): Server
{
    return Server::factory()->create([
        'project_id' => test()->server->project_id,
        'user_id' => test()->user->id,
        'status' => ServerStatus::READY,
        'provider_id' => $connection->id,
        'provider_data' => $region === null
            ? ['instance_id' => $instanceId]
            : ['instance_id' => $instanceId, 'region' => $region],
    ]);
}

function vitoPestFeatureNetworkProviderSyncTestAwsNetwork(ServerProvider $connection, string $externalId, Server $member): Network
{
    $network = Network::factory()->create([
        'project_id' => test()->server->project_id,
        'type' => NetworkType::PROVIDER,
        'server_provider_id' => $connection->id,
        'external_id' => $externalId,
    ]);

    $network->servers()->create([
        'server_id' => $member->id,
        'ip' => '172.31.0.5',
        'status' => NetworkServerStatus::ACTIVE,
    ]);

    return $network;
}

test('vpc without managed servers is not imported', function () {
    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 9999, 'name' => 'someone-elses', 'ip_range' => '10.9.0.0/16', 'servers' => [777]]], [['id' => 777, 'private_net' => [['network' => 9999, 'ip' => '10.9.0.2']]]]);

    vitoPestFeatureNetworkProviderSyncTestSync();

    expect(Network::query()->where('type', NetworkType::PROVIDER)->count())->toBe(0);
});

test('imported network firewall uses member ips not vpc cidr', function () {
    $peer = vitoPestFeatureNetworkProviderSyncTestOtherServer();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '172.31.0.0/16', 'servers' => [101, 102]]], [
        ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '172.31.0.2']]],
        ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '172.31.0.3']]],
    ]);

    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();

    expect(ServerNetworkRule::query()->where('network_id', $network->id)->where('mask', 16)->count())->toBe(0, 'Sync must never widen the firewall to the whole VPC.');

    $sources = ServerNetworkRule::query()
        ->where('network_id', $network->id)
        ->where('server_id', $this->server->id)
        ->pluck('source')
        ->all();

    expect($sources)->toContain('172.31.0.3');
    expect($sources)->not->toContain('172.31.0.0');
    expect($peer->id)->not->toBeNull();
});

test('name collision is uniqued', function () {
    Network::factory()->create([
        'project_id' => $this->server->project_id,
        'name' => 'prod',
        'type' => NetworkType::CUSTOM,
    ]);

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);

    vitoPestFeatureNetworkProviderSyncTestSync();

    $this->assertDatabaseHas('networks', ['external_id' => '4711', 'name' => 'prod-2']);
});

test('provider name change does not rename the local network', function () {
    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'renamed-at-provider', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]], [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $this->assertDatabaseHas('networks', ['external_id' => '4711', 'name' => 'prod']);
});

test('recycled ip between members does not violate unique index', function () {
    $peer = vitoPestFeatureNetworkProviderSyncTestOtherServer();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]], [
        ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
        ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
    ]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    vitoPestFeatureNetworkProviderSyncTestFakeProvider([['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]], [
        ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
        ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
    ]);
    vitoPestFeatureNetworkProviderSyncTestSync();

    $network = Network::query()->where('external_id', '4711')->firstOrFail();

    expect($network->servers()->where('server_id', $this->server->id)->value('ip'))->toBe('10.0.0.3');
    expect($network->servers()->where('server_id', $peer->id)->value('ip'))->toBe('10.0.0.2');
});

function vitoPestFeatureNetworkProviderSyncTestProviderNetwork(?int $connectionId = null): Network
{
    return Network::factory()->create([
        'project_id' => test()->server->project_id,
        'type' => NetworkType::PROVIDER,
        'server_provider_id' => $connectionId,
        'external_id' => '4711',
    ]);
}

test('provider network cannot be deleted', function () {
    $network = vitoPestFeatureNetworkProviderSyncTestProviderNetwork($this->connection->id);

    $this->actingAs($this->user)
        ->delete(route('networks.destroy', $network))
        ->assertForbidden();

    $this->assertDatabaseHas('networks', ['id' => $network->id]);
});

test('orphaned provider network can be deleted', function () {
    $network = vitoPestFeatureNetworkProviderSyncTestProviderNetwork(null);

    $this->actingAs($this->user)
        ->delete(route('networks.destroy', $network))
        ->assertRedirect();

    $this->assertNotSame(NetworkStatus::ACTIVE, $network->fresh()?->status);
});

test('manual member routes are rejected on a provider network', function () {
    $network = vitoPestFeatureNetworkProviderSyncTestProviderNetwork($this->connection->id);
    $member = $network->servers()->create([
        'server_id' => $this->server->id,
        'ip' => '10.0.0.2',
        'status' => NetworkServerStatus::ACTIVE,
    ]);

    $this->actingAs($this->user);

    $this->post(route('networks.servers.store', $network), ['servers' => [$this->server->id]])
        ->assertNotFound();

    $this->put(route('networks.servers.update', ['network' => $network, 'networkServer' => $member]), [])
        ->assertNotFound();

    $this->post(route('networks.servers.sync', ['network' => $network, 'networkServer' => $member]))
        ->assertNotFound();

    $this->delete(route('networks.servers.destroy', ['network' => $network, 'networkServer' => $member]))
        ->assertNotFound();

    $this->assertDatabaseHas('network_servers', ['id' => $member->id, 'status' => NetworkServerStatus::ACTIVE->value]);
});

test('global sync route dispatches the job', function () {
    Queue::fake();

    $this->actingAs($this->user)
        ->post(route('networks.sync-providers'))
        ->assertRedirect();

    Queue::assertPushed(SyncProviderNetworksJob::class);
});

test('repeated sync clicks are debounced', function () {
    Queue::fake();

    $this->actingAs($this->user);

    $this->post(route('networks.sync-providers'))->assertRedirect();
    $this->post(route('networks.sync-providers'))->assertRedirect();

    Queue::assertPushed(SyncProviderNetworksJob::class, 1);
});

test('viewer cannot trigger a provider sync', function () {
    Queue::fake();

    $viewer = User::factory()->create();
    $this->server->project->users()->create(['user_id' => $viewer->id, 'role' => UserRole::USER]);
    $viewer->current_project_id = $this->server->project_id;
    $viewer->save();

    $this->actingAs($viewer)
        ->post(route('networks.sync-providers'))
        ->assertForbidden();

    Queue::assertNothingPushed();
});

test('sync ignores networks of another project', function () {
    $other = Network::factory()->create([
        'project_id' => $this->server->project_id,
        'type' => NetworkType::PROVIDER,
        'server_provider_id' => $this->connection->id,
        'external_id' => '4711',
    ]);

    $otherUser = User::factory()->create();
    $otherUser->ensureHasDefaultProject();

    $before = $other->only(['project_id', 'server_provider_id', 'external_id', 'name', 'status', 'cidr', 'last_synced_at']);

    app(SyncProviderNetworks::class)->forProject($otherUser->currentProject, $other);

    $this->assertDatabaseHas('networks', ['id' => $other->id]);
    expect($other->fresh()?->only(['project_id', 'server_provider_id', 'external_id', 'name', 'status', 'cidr', 'last_synced_at']))->toBe($before, 'Syncing one project must not write to another project\'s network.');
    expect($other->servers()->count())->toBe(0);
});
