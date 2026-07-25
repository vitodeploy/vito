<?php

namespace Tests\Feature;

use App\Actions\Network\AddServersToNetwork;
use App\Actions\Network\AllocateNetworkBlock;
use App\Actions\Network\CreateNetwork;
use App\Actions\Network\CreateNetworkPeer;
use App\Actions\Network\DeleteNetwork;
use App\Actions\Network\RemoveServerFromNetwork;
use App\Actions\Network\SyncNetwork;
use App\Actions\ServerIp\ManageServerIp;
use App\Actions\ServerIp\RefreshServerIps;
use App\Enums\IpAddressFamily;
use App\Enums\IpAddressType;
use App\Enums\NetworkAddressingPool;
use App\Enums\NetworkPeerStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Facades\SSH;
use App\Jobs\Network\SyncNetworkServerJob;
use App\Models\NetworkServer;
use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Models\User;
use App\Support\Cidr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NetworkSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_cidr_helpers(): void
    {
        $this->assertSame('100.64.0.0/24', Cidr::canonical('100.64.0.5/24'));
        $this->assertTrue(Cidr::overlaps('10.0.0.0/16', '10.0.5.0/24'));
        $this->assertFalse(Cidr::overlaps('10.0.0.0/24', '10.1.0.0/24'));
        $this->assertSame('100.64.0.2', Cidr::nextHost('100.64.0.0/24', []));
        $this->assertSame('100.64.0.3', Cidr::nextHost('100.64.0.0/24', ['100.64.0.2']));
    }

    public function test_block_allocator_skips_member_subnet(): void
    {
        ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'ip' => '100.64.0.10',
            'prefix_length' => 24,
            'type' => IpAddressType::PUBLIC,
        ]);

        $cidr = app(AllocateNetworkBlock::class)->allocate(
            NetworkAddressingPool::CGNAT,
            24,
            collect(),
            collect([$this->server])
        );

        $this->assertSame('100.64.1.0/24', $cidr);
    }

    public function test_create_wireguard_network_allocates_block_and_activates_ready_member(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        $this->assertStringStartsWith('100.64.', (string) $network->cidr);

        $member = $network->servers()->firstOrFail();
        $this->assertSame('100.64.0.2', $member->ip);
        $this->assertNotNull($member->public_key);
        $this->assertSame(NetworkServerStatus::ACTIVE, $member->fresh()->status);
        $this->assertSame(NetworkStatus::ACTIVE, $network->fresh()->status);
        $this->assertNotNull($this->server->fresh()->service('vpn'));
        SSH::assertExecutedContains('wg-quick@wg-vito-'.$network->id);
    }

    public function test_create_wireguard_member_pending_when_server_not_ready_then_reconciler_activates(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::DISCONNECTED]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        $member = $network->servers()->firstOrFail();
        $this->assertSame(NetworkServerStatus::PENDING, $member->status);
        $this->assertSame(NetworkStatus::SYNCING, $network->fresh()->status);

        $this->server->update(['status' => ServerStatus::READY]);
        Artisan::call('networks:reconcile');

        $this->assertSame(NetworkServerStatus::ACTIVE, $member->fresh()->status);
        $this->assertSame(NetworkStatus::ACTIVE, $network->fresh()->status);
    }

    public function test_custom_network_members_are_active_without_wireguard(): void
    {
        SSH::fake();

        $ip = ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'ip' => '10.0.0.5',
            'type' => IpAddressType::PRIVATE,
        ]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'prov-net',
            'type' => 'custom',
            'servers' => [$this->server->id],
            'ip_addresses' => [$this->server->id => $ip->id],
        ]);

        $member = $network->servers()->firstOrFail();
        $this->assertSame(NetworkServerStatus::ACTIVE, $member->status);
        $this->assertSame($ip->id, $member->server_ip_address_id);
        $this->assertSame(NetworkStatus::ACTIVE, $network->fresh()->status);
        $this->assertNull($this->server->fresh()->service('vpn'));
        SSH::assertNotExecutedContains('wg-quick');
    }

    public function test_create_rejects_zero_servers(): void
    {
        $this->expectException(ValidationException::class);

        app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'empty',
            'type' => 'wireguard',
            'servers' => [],
        ]);
    }

    public function test_custom_network_rejects_a_public_ip(): void
    {
        $ip = ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'ip' => '1.2.3.4',
            'type' => IpAddressType::PUBLIC,
        ]);

        $this->expectException(ValidationException::class);

        app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'prov-net',
            'type' => 'custom',
            'servers' => [$this->server->id],
            'ip_addresses' => [$this->server->id => $ip->id],
        ]);
    }

    public function test_remove_last_wireguard_member_tears_down_and_uninstalls(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        $member = $network->servers()->firstOrFail();

        app(RemoveServerFromNetwork::class)->remove($member->fresh());

        $this->assertDatabaseMissing('network_servers', ['id' => $member->id]);
        $this->assertNull($this->server->fresh()->service('vpn'));
        SSH::assertExecutedContains('wg-quick down wg-vito-'.$network->id);
        SSH::assertExecutedContains('apt-get remove -y wireguard');
    }

    public function test_delete_network_removes_members_and_network(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        app(DeleteNetwork::class)->delete($network);

        $this->assertDatabaseMissing('networks', ['id' => $network->id]);
        $this->assertDatabaseMissing('network_servers', ['network_id' => $network->id]);
    }

    public function test_teardown_failure_recomputes_network_status(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        $member = $network->servers()->firstOrFail();

        NetworkServer::query()->whereKey($member->id)->update([
            'status' => NetworkServerStatus::LEAVING,
        ]);

        (new SyncNetworkServerJob($member->fresh(), true))->failed(new \Exception('boom'));

        $this->assertDatabaseHas('network_servers', ['id' => $member->id]);
        $this->assertSame(NetworkStatus::SYNCING, $network->fresh()->status);
    }

    /**
     * Cleanup on the server can only run while it is reachable, so a teardown that has burned
     * its fast attempts keeps retrying on the slow cadence — abandoning it there would leave the
     * tunnel configured on a server that was merely rebooting.
     */
    public function test_reconciler_keeps_retrying_a_leaving_member_past_the_fast_attempts(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        $member = $network->servers()->firstOrFail();

        NetworkServer::query()->whereKey($member->id)->update([
            'status' => NetworkServerStatus::LEAVING,
            'sync_attempts' => 5,
            'updated_at' => now()->subMinutes(3),
        ]);

        Artisan::call('networks:reconcile');

        $this->assertDatabaseHas('network_servers', ['id' => $member->id]);

        NetworkServer::query()->whereKey($member->id)->update([
            'status' => NetworkServerStatus::LEAVING,
            'updated_at' => now()->subMinutes(61),
        ]);

        Artisan::call('networks:reconcile');

        $this->assertDatabaseMissing('network_servers', ['id' => $member->id]);
        $this->assertDatabaseMissing('server_logs', [
            'network_id' => $network->id,
            'type' => 'network-leave-incomplete',
        ]);
    }

    /**
     * The retries are bounded so a server that never comes back cannot strand the network in
     * `deleting` forever. The membership goes; the log records that the server may still hold
     * the tunnel configuration.
     */
    public function test_reconciler_force_converges_a_leaving_member_that_never_converges(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        $member = $network->servers()->firstOrFail();

        NetworkServer::query()->whereKey($member->id)->update([
            'status' => NetworkServerStatus::LEAVING,
            'sync_attempts' => 30,
            'updated_at' => now()->subMinutes(3),
        ]);

        Artisan::call('networks:reconcile');

        $this->assertDatabaseMissing('network_servers', ['id' => $member->id]);
        $this->assertDatabaseHas('server_logs', [
            'network_id' => $network->id,
            'type' => 'network-leave-incomplete',
        ]);
    }

    public function test_create_wireguard_rejects_when_block_is_full(): void
    {
        SSH::fake();

        $servers = [$this->server->id];
        for ($i = 0; $i < 13; $i++) {
            $servers[] = Server::factory()->create([
                'project_id' => $this->server->project_id,
                'user_id' => $this->user->id,
                'status' => ServerStatus::DISCONNECTED,
            ])->id;
        }

        try {
            app(CreateNetwork::class)->create($this->server->project, [
                'name' => 'wg-full',
                'type' => 'wireguard',
                'prefix' => 28,
                'servers' => $servers,
            ]);
            $this->fail('Expected a full-block ValidationException.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('servers', $e->errors());
        }

        $this->assertDatabaseMissing('networks', ['name' => 'wg-full']);
    }

    public function test_add_server_to_wireguard_network_assigns_distinct_ip(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        $server2 = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        app(AddServersToNetwork::class)->add($network, ['servers' => [$server2->id]]);

        $ips = $network->servers()->pluck('ip');
        $this->assertSame(2, $ips->count());
        $this->assertSame($ips->count(), $ips->unique()->count());
        $this->assertContains('100.64.0.3', $ips->all());
    }

    public function test_add_server_rejects_cross_project_server(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        $otherUser = User::factory()->create();
        $otherUser->ensureHasDefaultProject();
        $foreign = Server::factory()->create([
            'project_id' => $otherUser->current_project_id,
            'user_id' => $otherUser->id,
            'status' => ServerStatus::READY,
        ]);

        $this->expectException(ValidationException::class);

        app(AddServersToNetwork::class)->add($network, ['servers' => [$foreign->id]]);
    }

    public function test_sync_reinstalls_wireguard_when_service_not_ready(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $this->server->services()->create([
            'type' => 'vpn',
            'name' => 'wireguard',
            'version' => 'latest',
            'status' => ServiceStatus::INSTALLATION_FAILED,
        ]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        SSH::assertExecutedContains('apt-get install -y wireguard');
        $this->assertSame(ServiceStatus::READY, $this->server->fresh()->service('vpn')->status);
        $this->assertSame(NetworkServerStatus::ACTIVE, $network->servers()->firstOrFail()->status);
    }

    public function test_reconciler_retries_failed_member_when_server_ready(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        $member = $network->servers()->firstOrFail();

        NetworkServer::query()->whereKey($member->id)->update([
            'status' => NetworkServerStatus::FAILED,
            'sync_attempts' => 1,
        ]);

        Artisan::call('networks:reconcile');

        $fresh = $member->fresh();
        $this->assertSame(NetworkServerStatus::ACTIVE, $fresh->status);
        $this->assertSame(0, $fresh->sync_attempts);
    }

    /**
     * A member that burns through its fast attempts drops to an hourly retry rather than being
     * abandoned: giving up permanently would keep the network `failed` forever and would never
     * pick up a later change, with nothing in the UI to say a manual sync is needed.
     */
    public function test_reconciler_retries_an_exhausted_member_on_the_slow_cadence(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        $member = $network->servers()->firstOrFail();

        NetworkServer::query()->whereKey($member->id)->update([
            'status' => NetworkServerStatus::FAILED,
            'sync_attempts' => 5,
            'updated_at' => now()->subMinutes(5),
        ]);

        Artisan::call('networks:reconcile');

        $this->assertSame(
            NetworkServerStatus::FAILED,
            $member->fresh()?->status,
            'An exhausted member must not be retried again on the fast cadence.'
        );

        NetworkServer::query()->whereKey($member->id)->update(['updated_at' => now()->subMinutes(61)]);

        Artisan::call('networks:reconcile');

        $this->assertSame(NetworkServerStatus::ACTIVE, $member->fresh()?->status);
    }

    /**
     * A custom membership is addressed solely by its IP row, so the membership has to go when
     * the address does. The refresh used to drop these rows with a query-builder delete, which
     * fires no model events, leaving the membership behind with a null address.
     */
    public function test_refresh_removes_a_custom_membership_when_its_address_disappears(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $address = ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'ip' => '10.70.0.5',
            'prefix_length' => 24,
            'family' => IpAddressFamily::V4,
            'type' => IpAddressType::PRIVATE,
            'is_managed' => false,
        ]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'custom-net',
            'type' => 'custom',
            'cidr' => '10.70.0.0/24',
            'servers' => [$this->server->id],
            'ip_addresses' => [$this->server->id => $address->id],
        ]);

        $this->assertSame(1, $network->servers()->count());

        SSH::fake(json_encode([[
            'ifname' => 'eth0',
            'addr_info' => [[
                'family' => 'inet',
                'local' => '10.70.0.9',
                'prefixlen' => 24,
                'scope' => 'global',
            ]],
        ]]));

        app(RefreshServerIps::class)->handle($this->server);

        $this->assertDatabaseMissing('server_ip_addresses', ['id' => $address->id]);
        $this->assertSame(0, $network->servers()->where('status', '!=', NetworkServerStatus::LEAVING)->count());
    }

    /**
     * A network losing its last member has no sibling left whose sync would recompute it, so the
     * status has to be recomputed from the departure itself — otherwise the network reports
     * `active` with no servers, and its peers report connected to nothing.
     */
    /**
     * A peer's WireGuard endpoint is the server's public address, so promoting a different one
     * has to push the change to the other members — the same resync an explicit IP edit does.
     * Without it they keep dialling the old address.
     */
    public function test_promoting_a_public_ip_resyncs_wireguard_members(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $other = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id, $other->id],
        ]);

        $promoted = ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'ip' => '203.0.113.9',
            'family' => IpAddressFamily::V4,
            'type' => IpAddressType::PUBLIC,
        ]);

        SSH::fake();

        app(ManageServerIp::class)->setPrimary($promoted);

        $this->assertSame('203.0.113.9', $this->server->fresh()?->ip);
        $this->assertStringContainsString(
            'Endpoint = 203.0.113.9:'.$network->port,
            SSH::getUploadedContent(),
            "The other member's tunnel must point at the new address."
        );
    }

    public function test_deleting_the_last_member_server_recomputes_the_network(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        $peer = app(CreateNetworkPeer::class)->create($network, ['name' => 'laptop']);

        $this->assertSame(NetworkStatus::ACTIVE, $network->fresh()?->status);
        $this->assertSame(NetworkPeerStatus::ACTIVE, $peer->fresh()?->status);

        $this->server->delete();

        $this->assertSame(0, $network->servers()->count());
        $this->assertSame(NetworkStatus::CREATING, $network->fresh()?->status);
        $this->assertSame(NetworkPeerStatus::PENDING, $peer->fresh()?->status);
    }

    public function test_deleting_server_resyncs_wireguard_siblings(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $server2 = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id, $server2->id],
        ]);
        $sibling = $network->servers()->where('server_id', $server2->id)->firstOrFail();
        $departingKey = (string) $network->servers()->where('server_id', $this->server->id)->firstOrFail()->public_key;

        SSH::fake();

        $this->server->deleteFromProvider = false;
        $this->server->delete();

        $this->assertDatabaseMissing('network_servers', ['server_id' => $this->server->id]);
        $this->assertSame(1, $network->servers()->count());
        $this->assertSame(NetworkServerStatus::ACTIVE, $sibling->fresh()->status);

        SSH::assertExecutedContains('wg-quick@wg-vito-'.$network->id);
        $this->assertStringNotContainsString(
            $departingKey,
            SSH::getUploadedContent(),
            "The sibling's tunnel must be rewritten without the departed server's key."
        );
    }

    public function test_leaving_one_of_two_wireguard_networks_keeps_service_installed(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $networkA = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-a',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-b',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        $memberA = $networkA->servers()->firstOrFail();
        app(RemoveServerFromNetwork::class)->remove($memberA->fresh());

        $this->assertDatabaseMissing('network_servers', ['id' => $memberA->id]);
        $this->assertNotNull($this->server->fresh()->service('vpn'));
    }

    public function test_reconciler_recovers_stale_updating_member(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        $member = $network->servers()->firstOrFail();

        NetworkServer::query()->whereKey($member->id)->update([
            'status' => NetworkServerStatus::UPDATING,
            'updated_at' => now()->subMinutes(91),
        ]);

        Artisan::call('networks:reconcile');

        $this->assertSame(NetworkServerStatus::ACTIVE, $member->fresh()->status);
    }

    public function test_allocator_ignores_ipv6_member_address(): void
    {
        ServerIpAddress::factory()->create([
            'server_id' => $this->server->id,
            'ip' => 'fe80::1',
            'prefix_length' => 64,
            'family' => IpAddressFamily::V6,
            'type' => IpAddressType::PUBLIC,
        ]);

        $cidr = app(AllocateNetworkBlock::class)->allocate(
            NetworkAddressingPool::CGNAT,
            24,
            collect(),
            collect([$this->server])
        );

        $this->assertSame('100.64.0.0/24', $cidr);
    }

    public function test_two_wireguard_networks_sharing_a_server_get_distinct_ports(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $a = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-a',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        $b = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-b',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);

        $this->assertSame(51820, $a->port);
        $this->assertSame(51821, $b->port);
    }

    public function test_custom_network_accepts_a_second_server(): void
    {
        SSH::fake();
        $ip1 = ServerIpAddress::factory()->create(['server_id' => $this->server->id, 'ip' => '10.0.0.5', 'type' => IpAddressType::PRIVATE]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'prov',
            'type' => 'custom',
            'servers' => [$this->server->id],
            'ip_addresses' => [$this->server->id => $ip1->id],
        ]);

        $peer = Server::factory()->create([
            'project_id' => $this->server->project_id,
            'user_id' => $this->user->id,
            'status' => ServerStatus::READY,
        ]);
        $ip2 = ServerIpAddress::factory()->create(['server_id' => $peer->id, 'ip' => '10.0.0.6', 'type' => IpAddressType::PRIVATE]);

        app(AddServersToNetwork::class)->add($network, ['servers' => [$peer->id], 'ip_addresses' => [$peer->id => $ip2->id]]);

        $this->assertSame(2, $network->servers()->count());
        $this->assertSame(NetworkServerStatus::ACTIVE, $network->servers()->where('server_id', $peer->id)->firstOrFail()->status);
    }

    public function test_sync_network_member_regenerates_config(): void
    {
        SSH::fake();
        $this->server->update(['status' => ServerStatus::READY]);

        $network = app(CreateNetwork::class)->create($this->server->project, [
            'name' => 'wg-net',
            'type' => 'wireguard',
            'servers' => [$this->server->id],
        ]);
        $member = $network->servers()->firstOrFail();

        app(SyncNetwork::class)->member($member->fresh());

        $this->assertSame(NetworkServerStatus::ACTIVE, $member->fresh()->status);
        SSH::assertExecutedContains('wg-quick@wg-vito-'.$network->id);
    }
}
