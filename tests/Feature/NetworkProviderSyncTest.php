<?php

namespace Tests\Feature;

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
use RuntimeException;
use Tests\TestCase;

class NetworkProviderSyncTest extends TestCase
{
    use RefreshDatabase;

    private ServerProvider $connection;

    /** @var array<int, array<string, mixed>> */
    private array $networksPayload = [];

    /** @var array<int, array<string, mixed>> */
    private array $serversPayload = [];

    private ?int $failStatus = null;

    private bool $malformed = false;

    /**
     * `Http::fake()` merges stubs rather than replacing them, so a per-call fake would let
     * the first registration win for every later sync. One closure reading mutable state
     * is the only way to vary the response across runs within a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    private function otherServer(int $hetznerId = 102): Server
    {
        return Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
            'provider_id' => $this->connection->id,
            'provider_data' => ['hetzner_id' => $hetznerId, 'region' => 'nbg1'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $networks
     * @param  array<int, array<string, mixed>>  $servers
     */
    private function fakeProvider(array $networks, array $servers): void
    {
        $this->networksPayload = $networks;
        $this->serversPayload = $servers;
        $this->failStatus = null;
    }

    private function sync(?Network $only = null): void
    {
        app(SyncProviderNetworks::class)->forProject($this->server->project, $only);
    }

    public function test_creates_network_and_members_from_provider(): void
    {
        $peer = $this->otherServer();

        $this->fakeProvider(
            [[
                'id' => 4711,
                'name' => 'prod',
                'ip_range' => '10.0.0.0/16',
                'subnets' => [['network_zone' => 'eu-central']],
                'servers' => [101, 102],
            ]],
            [
                ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
                ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
            ],
        );

        $this->sync();

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
    }

    public function test_second_run_creates_no_duplicates(): void
    {
        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );

        $this->sync();
        $this->sync();

        $this->assertSame(1, Network::query()->where('external_id', '4711')->count());
        $this->assertSame(1, Network::query()->where('type', NetworkType::PROVIDER)->count());
    }

    public function test_adds_and_removes_members_as_provider_changes(): void
    {
        $peer = $this->otherServer();

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]],
            [
                ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
                ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
            ],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();
        $this->assertSame(2, $network->servers()->count());

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );
        $this->sync();

        $this->assertSame(
            0,
            $network->servers()->where('server_id', $peer->id)->whereNot('status', NetworkServerStatus::LEAVING)->count(),
            'A detached server must no longer be a live member.'
        );
    }

    public function test_reattached_server_revives_a_leaving_member_instead_of_throwing(): void
    {
        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();
        $member = $network->servers()->firstOrFail();
        $member->update(['status' => NetworkServerStatus::LEAVING, 'sync_attempts' => 3]);

        $this->sync();

        $member->refresh();
        $this->assertSame(NetworkServerStatus::ACTIVE, $member->status);
        $this->assertSame(0, $member->sync_attempts);
        $this->assertSame(1, $network->servers()->count(), 'Reviving must not insert a second member row.');
    }

    /**
     * The peer keeps the network alive so this exercises member removal, not network prune.
     */
    public function test_already_leaving_member_is_not_torn_down_again(): void
    {
        $peer = $this->otherServer();

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]],
            [
                ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
                ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
            ],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();
        $member = $network->servers()->where('server_id', $this->server->id)->firstOrFail();
        $member->update(['status' => NetworkServerStatus::LEAVING, 'sync_attempts' => 3]);

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [102]]],
            [['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]]],
        );
        $this->sync();

        $member->refresh();
        $this->assertSame(
            3,
            $member->sync_attempts,
            'Re-driving teardown would reset sync_attempts and defeat forceConverge escalation.'
        );
        $this->assertNotNull($peer->id);
    }

    public function test_resurrects_a_deleting_network_that_reappears(): void
    {
        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();
        $network->update(['status' => NetworkStatus::DELETING]);
        $network->servers()->update(['status' => NetworkServerStatus::LEAVING]);

        $this->sync();

        $network->refresh();
        $this->assertNotSame(NetworkStatus::DELETING, $network->status);
        $this->assertSame(
            0,
            $network->servers()->where('status', NetworkServerStatus::LEAVING)->count(),
            'Resurrected networks must not keep LEAVING members that never converge.'
        );
    }

    public function test_deletes_network_when_vpc_disappears(): void
    {
        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();

        $this->fakeProvider([], []);
        $this->sync();

        $this->assertNotSame(
            NetworkStatus::ACTIVE,
            $network->fresh()?->status,
            'A removed VPC must start teardown.'
        );
    }

    /**
     * A failed connection must leave its networks untouched rather than pruning them, and the
     * error must still reach the caller so the sync is not reported as a success.
     */
    public function test_provider_failure_never_prunes_and_is_rethrown(): void
    {
        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();

        $this->failStatus = 500;

        $threw = false;
        try {
            $this->sync();
        } catch (PrivateNetworkSyncError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'A provider failure must not be reported as a successful sync.');

        $network->refresh();
        $this->assertSame(NetworkStatus::ACTIVE, $network->status);
        $this->assertSame(1, $network->servers()->count());
    }

    /**
     * Deleting the last server on a connection leaves its provider networks with no members,
     * so nothing can reconcile them — and the policy refuses a manual delete while the network
     * is provider-managed. The sweep therefore still has to visit the connection and prune.
     */
    public function test_provider_network_is_pruned_after_its_last_managed_server_is_deleted(): void
    {
        $other = $this->otherServer();

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]],
            [
                ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
                ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
            ],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();
        $this->assertSame(2, $network->servers()->count());

        $other->delete();
        $this->server->delete();

        $this->assertSame(0, $network->servers()->count());

        $this->sync();

        $this->assertNull(
            Network::query()->find($network->id),
            'A provider network with no managed servers left must be pruned, not stranded.'
        );
    }

    /**
     * A connection can yield no instance ids while its servers still exist — for example when
     * `provider_data` is missing the id key. An empty result then says nothing about what is
     * still at the provider, so a network that has members must survive rather than be reaped.
     */
    public function test_network_with_live_members_survives_when_no_instance_ids_can_be_queried(): void
    {
        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();
        $this->assertSame(1, $network->servers()->count());

        $this->server->update(['provider_data' => ['region' => 'nbg1']]);

        $this->sync();

        $this->assertNotNull(
            Network::query()->find($network->id),
            'A network with live members must not be pruned when the provider could not be queried.'
        );
        $this->assertSame(1, $network->servers()->count());
    }

    /**
     * EC2 is queried per region, so a connection whose servers carry no region cannot be asked
     * at all. Reading that empty result as "the VPCs are gone" would delete every AWS network
     * on the connection, with its firewall rules, on a single sync.
     */
    public function test_aws_networks_survive_when_no_region_is_known(): void
    {
        $connection = $this->awsConnection();
        $server = $this->awsServer($connection, 'i-0abc');
        $network = $this->awsNetwork($connection, 'vpc-0abc', $server);

        $this->sync();

        $this->assertNotNull(
            Network::query()->find($network->id),
            'An AWS network must not be pruned when no region was available to query.'
        );
        $this->assertSame(1, $network->servers()->count());
    }

    /**
     * Only the regions actually collected are queried, so one server without a region leaves its
     * VPC unasked-about while the rest of the connection answers normally. Treating that as a
     * complete answer would delete precisely the network the guard exists to protect.
     */
    public function test_aws_networks_survive_when_one_server_has_no_region(): void
    {
        $connection = $this->awsConnection();
        $this->awsServer($connection, 'i-known', 'eu-west-1');
        $stranded = $this->awsServer($connection, 'i-unknown');
        $network = $this->awsNetwork($connection, 'vpc-unknown', $stranded);

        $this->sync();

        $this->assertNotNull(
            Network::query()->find($network->id),
            'A network in an unqueried region must not be pruned because other servers answered.'
        );
        $this->assertSame(1, $network->servers()->count());
    }

    /**
     * Sync spares a network it could not ask about, and the policy refuses to delete a network
     * whose connection still exists. A network no member can be identified at the provider by
     * falls into both, so it has to be deletable or it is stranded with no way out.
     */
    public function test_provider_network_no_member_can_be_identified_by_becomes_deletable(): void
    {
        $network = $this->providerNetwork($this->connection->id);
        $network->servers()->create([
            'server_id' => $this->server->id,
            'ip' => '10.0.0.2',
            'status' => NetworkServerStatus::ACTIVE,
        ]);

        $this->actingAs($this->user);

        $this->delete(route('networks.destroy', $network))->assertForbidden();

        $this->server->update(['provider_data' => ['region' => 'nbg1']]);

        $this->sync();

        $this->assertNotNull(
            Network::query()->find($network->id),
            'Sync cannot ask about this network, so it must not be pruned either.'
        );

        $this->delete(route('networks.destroy', $network))->assertRedirect();
        $this->assertNotSame(NetworkStatus::ACTIVE, $network->fresh()?->status);
    }

    /**
     * A 200 whose body is not the expected shape says nothing about the account's inventory.
     * Reading it as "no networks" would make an API change, a proxy, or a captive portal delete
     * every synced network on the connection.
     */
    public function test_malformed_provider_response_fails_the_sweep_instead_of_pruning(): void
    {
        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();

        $this->malformed = true;

        try {
            $this->sync();
            $this->fail('A malformed provider response must fail the sweep.');
        } catch (PrivateNetworkSyncError) {
            // expected
        }

        $this->assertNotNull(
            Network::query()->find($network->id),
            'A network must not be pruned on the strength of an unreadable response.'
        );
    }

    /**
     * Discovered networks that cannot be written leave the sweep incomplete. Pruning still runs
     * for the rest, but the job has to report failure rather than a silent partial sync.
     */
    public function test_a_network_that_cannot_be_persisted_fails_the_sweep(): void
    {
        $peer = $this->otherServer();

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]],
            [
                ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
                ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
            ],
        );

        $this->expectException(PrivateNetworkPersistError::class);

        $this->sync();

        $this->assertNotNull($peer);
    }

    /**
     * The client replaces its whole copy of the network from this payload, so a relation the
     * initial page load included has to be present here too — otherwise Settings blanks the
     * provider the moment any status change is broadcast.
     */
    public function test_status_broadcast_carries_the_provider_relation(): void
    {
        Event::fake([SocketEvent::class]);

        $network = $this->providerNetwork($this->connection->id);

        app(RecomputeNetworkStatus::class)->handle($network);

        $payload = null;

        Event::assertDispatched(SocketEvent::class, function (SocketEvent $event) use (&$payload): bool {
            if ($event->data->type === 'network.updated' && is_array($event->data->data)) {
                $payload = $event->data->data;
            }

            return true;
        });

        $this->assertIsArray($payload);
        $this->assertSame(Hetzner::id(), $payload['provider'] ?? null);
    }

    /**
     * Only the sweep's own exceptions are built credential-free; anything else can carry a token
     * in its message, and this log is written verbatim.
     */
    public function test_failed_job_does_not_log_an_unexpected_exception_message(): void
    {
        Log::spy();
        Notification::fake();

        (new SyncProviderNetworksJob($this->server->project))->failed(new RuntimeException('token=super-secret'));

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message, array $context): bool => $context['exception'] === RuntimeException::class
                && $context['reason'] === null
        );
    }

    private function awsConnection(): ServerProvider
    {
        return ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => AWS::id(),
            'profile' => 'aws-main',
            'credentials' => ['key' => 'key', 'secret' => 'secret'],
        ]);
    }

    private function awsServer(ServerProvider $connection, string $instanceId, ?string $region = null): Server
    {
        return Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
            'provider_id' => $connection->id,
            'provider_data' => $region === null
                ? ['instance_id' => $instanceId]
                : ['instance_id' => $instanceId, 'region' => $region],
        ]);
    }

    private function awsNetwork(ServerProvider $connection, string $externalId, Server $member): Network
    {
        $network = Network::factory()->create([
            'project_id' => $this->server->project_id,
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

    public function test_vpc_without_managed_servers_is_not_imported(): void
    {
        $this->fakeProvider(
            [['id' => 9999, 'name' => 'someone-elses', 'ip_range' => '10.9.0.0/16', 'servers' => [777]]],
            [['id' => 777, 'private_net' => [['network' => 9999, 'ip' => '10.9.0.2']]]],
        );

        $this->sync();

        $this->assertSame(0, Network::query()->where('type', NetworkType::PROVIDER)->count());
    }

    public function test_imported_network_firewall_uses_member_ips_not_vpc_cidr(): void
    {
        $peer = $this->otherServer();

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '172.31.0.0/16', 'servers' => [101, 102]]],
            [
                ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '172.31.0.2']]],
                ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '172.31.0.3']]],
            ],
        );

        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();

        $this->assertSame(
            0,
            ServerNetworkRule::query()->where('network_id', $network->id)->where('mask', 16)->count(),
            'Sync must never widen the firewall to the whole VPC.'
        );

        $sources = ServerNetworkRule::query()
            ->where('network_id', $network->id)
            ->where('server_id', $this->server->id)
            ->pluck('source')
            ->all();

        $this->assertContains('172.31.0.3', $sources);
        $this->assertNotContains('172.31.0.0', $sources);
        $this->assertNotNull($peer->id);
    }

    public function test_name_collision_is_uniqued(): void
    {
        Network::factory()->create([
            'project_id' => $this->server->project_id,
            'name' => 'prod',
            'type' => NetworkType::CUSTOM,
        ]);

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );

        $this->sync();

        $this->assertDatabaseHas('networks', ['external_id' => '4711', 'name' => 'prod-2']);
    }

    public function test_provider_name_change_does_not_rename_the_local_network(): void
    {
        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );
        $this->sync();

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'renamed-at-provider', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
            [['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]]],
        );
        $this->sync();

        $this->assertDatabaseHas('networks', ['external_id' => '4711', 'name' => 'prod']);
    }

    public function test_recycled_ip_between_members_does_not_violate_unique_index(): void
    {
        $peer = $this->otherServer();

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]],
            [
                ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
                ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
            ],
        );
        $this->sync();

        $this->fakeProvider(
            [['id' => 4711, 'name' => 'prod', 'ip_range' => '10.0.0.0/16', 'servers' => [101, 102]]],
            [
                ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
                ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
            ],
        );
        $this->sync();

        $network = Network::query()->where('external_id', '4711')->firstOrFail();

        $this->assertSame('10.0.0.3', $network->servers()->where('server_id', $this->server->id)->value('ip'));
        $this->assertSame('10.0.0.2', $network->servers()->where('server_id', $peer->id)->value('ip'));
    }

    private function providerNetwork(?int $connectionId = null): Network
    {
        return Network::factory()->create([
            'project_id' => $this->server->project_id,
            'type' => NetworkType::PROVIDER,
            'server_provider_id' => $connectionId,
            'external_id' => '4711',
        ]);
    }

    public function test_provider_network_cannot_be_deleted(): void
    {
        $network = $this->providerNetwork($this->connection->id);

        $this->actingAs($this->user)
            ->delete(route('networks.destroy', $network))
            ->assertForbidden();

        $this->assertDatabaseHas('networks', ['id' => $network->id]);
    }

    public function test_orphaned_provider_network_can_be_deleted(): void
    {
        $network = $this->providerNetwork(null);

        $this->actingAs($this->user)
            ->delete(route('networks.destroy', $network))
            ->assertRedirect();

        $this->assertNotSame(NetworkStatus::ACTIVE, $network->fresh()?->status);
    }

    public function test_manual_member_routes_are_rejected_on_a_provider_network(): void
    {
        $network = $this->providerNetwork($this->connection->id);
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
    }

    public function test_global_sync_route_dispatches_the_job(): void
    {
        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('networks.sync-providers'))
            ->assertRedirect();

        Queue::assertPushed(SyncProviderNetworksJob::class);
    }

    public function test_repeated_sync_clicks_are_debounced(): void
    {
        Queue::fake();

        $this->actingAs($this->user);

        $this->post(route('networks.sync-providers'))->assertRedirect();
        $this->post(route('networks.sync-providers'))->assertRedirect();

        Queue::assertPushed(SyncProviderNetworksJob::class, 1);
    }

    public function test_viewer_cannot_trigger_a_provider_sync(): void
    {
        Queue::fake();

        $viewer = User::factory()->create();
        $this->server->project->users()->create(['user_id' => $viewer->id, 'role' => UserRole::USER]);
        $viewer->current_project_id = $this->server->project_id;
        $viewer->save();

        $this->actingAs($viewer)
            ->post(route('networks.sync-providers'))
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_sync_ignores_networks_of_another_project(): void
    {
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
        $this->assertSame(
            $before,
            $other->fresh()?->only(['project_id', 'server_provider_id', 'external_id', 'name', 'status', 'cidr', 'last_synced_at']),
            'Syncing one project must not write to another project\'s network.'
        );
        $this->assertSame(0, $other->servers()->count());
    }
}
