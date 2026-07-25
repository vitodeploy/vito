<?php

namespace Tests\Feature;

use App\Exceptions\PrivateNetworkSyncError;
use App\Models\ServerProvider;
use App\ServerProviders\AWS;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Vultr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProviderPrivateNetworkTest extends TestCase
{
    use RefreshDatabase;

    private function hetznerConnection(): ServerProvider
    {
        return ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Hetzner::id(),
            'profile' => 'hetzner-main',
            'credentials' => ['token' => 'secret-token'],
        ]);
    }

    public function test_hetzner_maps_networks_and_member_ips(): void
    {
        Http::fake([
            'api.hetzner.cloud/v1/networks*' => Http::response([
                'networks' => [
                    [
                        'id' => 4711,
                        'name' => 'prod-net',
                        'ip_range' => '10.0.0.0/16',
                        'subnets' => [['network_zone' => 'eu-central']],
                        'servers' => [101, 102, 999],
                    ],
                ],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
            'api.hetzner.cloud/v1/servers*' => Http::response([
                'servers' => [
                    ['id' => 101, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.2']]],
                    ['id' => 102, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.3']]],
                    ['id' => 999, 'private_net' => [['network' => 4711, 'ip' => '10.0.0.9']]],
                ],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
        ]);

        /** @var Hetzner $provider */
        $provider = $this->hetznerConnection()->provider();

        $networks = $provider->privateNetworks(['101', '102'], []);

        $this->assertCount(1, $networks);
        $this->assertSame('4711', $networks[0]->externalId);
        $this->assertSame('prod-net', $networks[0]->name);
        $this->assertSame('10.0.0.0/16', $networks[0]->cidr);
        $this->assertSame('eu-central', $networks[0]->region);

        $this->assertCount(2, $networks[0]->members, 'Instances Vito does not manage must be ignored.');
        $this->assertSame(['101', '102'], array_map(fn ($m): string => $m->instanceId, $networks[0]->members));
        $this->assertSame(['10.0.0.2', '10.0.0.3'], array_map(fn ($m): ?string => $m->ip, $networks[0]->members));
    }

    public function test_hetzner_skips_networks_with_no_managed_members(): void
    {
        Http::fake([
            'api.hetzner.cloud/v1/networks*' => Http::response([
                'networks' => [
                    ['id' => 1, 'name' => 'unrelated', 'ip_range' => '10.1.0.0/16', 'servers' => [777]],
                ],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
            'api.hetzner.cloud/v1/servers*' => Http::response([
                'servers' => [],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
        ]);

        /** @var Hetzner $provider */
        $provider = $this->hetznerConnection()->provider();

        $this->assertSame([], $provider->privateNetworks(['101'], []));
    }

    public function test_hetzner_follows_pagination(): void
    {
        Http::fakeSequence('api.hetzner.cloud/v1/networks*')
            ->push([
                'networks' => [['id' => 1, 'name' => 'a', 'ip_range' => '10.1.0.0/16', 'servers' => []]],
                'meta' => ['pagination' => ['next_page' => 2]],
            ])
            ->push([
                'networks' => [['id' => 2, 'name' => 'b', 'ip_range' => '10.2.0.0/16', 'servers' => [101]]],
                'meta' => ['pagination' => ['next_page' => null]],
            ]);

        Http::fake([
            'api.hetzner.cloud/v1/servers*' => Http::response([
                'servers' => [['id' => 101, 'private_net' => [['network' => 2, 'ip' => '10.2.0.4']]]],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
        ]);

        /** @var Hetzner $provider */
        $provider = $this->hetznerConnection()->provider();

        $networks = $provider->privateNetworks(['101'], []);

        $this->assertCount(1, $networks);
        $this->assertSame('2', $networks[0]->externalId);
        $this->assertSame('10.2.0.4', $networks[0]->members[0]->ip);
    }

    public function test_hetzner_error_does_not_leak_credentials(): void
    {
        Http::fake([
            'api.hetzner.cloud/v1/*' => Http::response(['error' => ['message' => 'forbidden']], 403),
        ]);

        /** @var Hetzner $provider */
        $provider = $this->hetznerConnection()->provider();

        try {
            $provider->privateNetworks(['101'], []);
            $this->fail('Expected PrivateNetworkSyncError.');
        } catch (PrivateNetworkSyncError $e) {
            $this->assertTrue($e->isPermissionError());
            $this->assertStringNotContainsString('secret-token', $e->getMessage());
            $this->assertStringNotContainsString('secret-token', (string) $e);
            $this->assertSame('hetzner', $e->provider);
            $this->assertSame('hetzner-main', $e->profile);
        }
    }

    public function test_digitalocean_uses_droplet_vpc_uuid_and_private_address(): void
    {
        Http::fake([
            'api.digitalocean.com/v2/vpcs*' => Http::response([
                'vpcs' => [
                    ['id' => 'vpc-abc', 'name' => 'default-lon1', 'ip_range' => '10.106.0.0/20', 'region' => 'lon1'],
                ],
            ]),
            'api.digitalocean.com/v2/droplets*' => Http::response([
                'droplets' => [
                    [
                        'id' => 3164444,
                        'vpc_uuid' => 'vpc-abc',
                        'networks' => ['v4' => [
                            ['type' => 'public', 'ip_address' => '203.0.113.5'],
                            ['type' => 'private', 'ip_address' => '10.106.0.4'],
                        ]],
                    ],
                    ['id' => 999, 'vpc_uuid' => 'vpc-abc', 'networks' => ['v4' => []]],
                ],
            ]),
        ]);

        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => DigitalOcean::id(),
            'credentials' => ['token' => 'secret-token'],
        ]);

        /** @var DigitalOcean $provider */
        $provider = $connection->provider();

        $networks = $provider->privateNetworks(['3164444'], []);

        $this->assertCount(1, $networks);
        $this->assertSame('vpc-abc', $networks[0]->externalId);
        $this->assertSame('10.106.0.0/20', $networks[0]->cidr);
        $this->assertCount(1, $networks[0]->members, 'Unmanaged droplets must be excluded.');
        $this->assertSame('10.106.0.4', $networks[0]->members[0]->ip);
    }

    public function test_linode_reads_membership_from_subnets_and_ips_from_account_list(): void
    {
        Http::fake([
            'api.linode.com/v4/vpcs/ips*' => Http::response([
                'data' => [
                    ['linode_id' => 555, 'vpc_id' => 77, 'address' => '10.0.1.4', 'active' => true],
                    ['linode_id' => 555, 'vpc_id' => 77, 'address' => null, 'address_range' => '10.0.9.0/28'],
                ],
                'page' => 1,
                'pages' => 1,
            ]),
            'api.linode.com/v4/vpcs*' => Http::response([
                'data' => [
                    [
                        'id' => 77,
                        'label' => 'prod-vpc',
                        'region' => 'eu-west',
                        'subnets' => [
                            ['ipv4' => '10.0.1.0/24', 'linodes' => [['id' => 555], ['id' => 888]]],
                        ],
                    ],
                ],
                'page' => 1,
                'pages' => 1,
            ]),
        ]);

        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Linode::id(),
            'credentials' => ['token' => 'secret-token'],
        ]);

        /** @var Linode $provider */
        $provider = $connection->provider();

        $networks = $provider->privateNetworks(['555'], []);

        $this->assertCount(1, $networks);
        $this->assertSame('prod-vpc', $networks[0]->name);
        $this->assertSame('10.0.1.0/24', $networks[0]->cidr);
        $this->assertCount(1, $networks[0]->members);
        $this->assertSame('10.0.1.4', $networks[0]->members[0]->ip);
    }

    /**
     * A page count the API never stops advancing past would otherwise be walked until the job's
     * timeout. Failing beats returning what was collected so far: a partial view of the account
     * would look like the missing VPCs are gone, and sync prunes on that.
     */
    public function test_linode_stops_and_fails_on_a_runaway_page_count(): void
    {
        Http::fake([
            'api.linode.com/v4/*' => Http::response([
                'data' => [],
                'page' => 1,
                'pages' => 100000,
            ]),
        ]);

        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Linode::id(),
            'credentials' => ['token' => 'secret-token'],
        ]);

        /** @var Linode $provider */
        $provider = $connection->provider();

        $this->expectException(PrivateNetworkSyncError::class);

        $provider->privateNetworks(['555'], []);
    }

    public function test_linode_multi_subnet_vpc_reports_no_single_cidr(): void
    {
        Http::fake([
            'api.linode.com/v4/vpcs/ips*' => Http::response(['data' => [], 'page' => 1, 'pages' => 1]),
            'api.linode.com/v4/vpcs*' => Http::response([
                'data' => [[
                    'id' => 77,
                    'label' => 'multi',
                    'subnets' => [
                        ['ipv4' => '10.0.1.0/24', 'linodes' => [['id' => 555]]],
                        ['ipv4' => '10.0.2.0/24', 'linodes' => []],
                    ],
                ]],
                'page' => 1,
                'pages' => 1,
            ]),
        ]);

        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Linode::id(),
            'credentials' => ['token' => 'secret-token'],
        ]);

        /** @var Linode $provider */
        $provider = $connection->provider();

        $networks = $provider->privateNetworks(['555'], []);

        $this->assertNull(
            $networks[0]->cidr,
            'A VPC with several subnets has no single CIDR; firewall rules fall back to member /32s.'
        );
    }

    public function test_vultr_builds_cidr_from_v4_subnet_and_mask(): void
    {
        Http::fake([
            'api.vultr.com/v2/instances/*/vpcs*' => Http::response([
                'vpcs' => [['id' => 'vpc-1', 'ip_address' => '10.20.0.4', 'mac_address' => 'aa:bb']],
            ]),
            'api.vultr.com/v2/vpcs*' => Http::response([
                'vpcs' => [[
                    'id' => 'vpc-1',
                    'description' => 'prod-vpc',
                    'v4_subnet' => '10.20.0.0',
                    'v4_subnet_mask' => 24,
                    'region' => 'lhr',
                ]],
            ]),
        ]);

        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Vultr::id(),
            'credentials' => ['token' => 'secret-token'],
        ]);

        /** @var Vultr $provider */
        $provider = $connection->provider();

        $networks = $provider->privateNetworks(['inst-1'], []);

        $this->assertCount(1, $networks);
        $this->assertSame('prod-vpc', $networks[0]->name);
        $this->assertSame('10.20.0.0/24', $networks[0]->cidr);
        $this->assertSame('10.20.0.4', $networks[0]->members[0]->ip);
    }

    public function test_vultr_stale_instance_id_does_not_abort_the_connection(): void
    {
        Http::fake([
            'api.vultr.com/v2/instances/gone/vpcs*' => Http::response(['error' => 'not found'], 404),
            'api.vultr.com/v2/instances/*/vpcs*' => Http::response([
                'vpcs' => [['id' => 'vpc-1', 'ip_address' => '10.20.0.4']],
            ]),
            'api.vultr.com/v2/vpcs*' => Http::response([
                'vpcs' => [['id' => 'vpc-1', 'description' => 'prod', 'v4_subnet' => '10.20.0.0', 'v4_subnet_mask' => 24]],
            ]),
        ]);

        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Vultr::id(),
            'credentials' => ['token' => 'secret-token'],
        ]);

        /** @var Vultr $provider */
        $provider = $connection->provider();

        $networks = $provider->privateNetworks(['gone', 'live'], []);

        $this->assertCount(1, $networks, 'A deleted instance must not abort the whole connection.');
        $this->assertSame('10.20.0.4', $networks[0]->members[0]->ip);
        $this->assertSame('live', $networks[0]->members[0]->instanceId);
    }

    public function test_vultr_non_404_error_still_fails_the_connection(): void
    {
        Http::fake([
            'api.vultr.com/v2/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Vultr::id(),
            'credentials' => ['token' => 'secret-token'],
        ]);

        /** @var Vultr $provider */
        $provider = $connection->provider();

        $this->expectException(PrivateNetworkSyncError::class);
        $provider->privateNetworks(['live'], []);
    }

    public function test_aws_mapper_prefers_network_interface_and_name_tag(): void
    {
        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => AWS::id(),
            'credentials' => ['key' => 'k', 'secret' => 's'],
        ]);

        /** @var AWS $provider */
        $provider = $connection->provider();

        $reservations = [[
            'Instances' => [
                [
                    'InstanceId' => 'i-123',
                    'VpcId' => 'vpc-top',
                    'PrivateIpAddress' => '172.31.9.9',
                    'NetworkInterfaces' => [
                        ['VpcId' => 'vpc-eni', 'PrivateIpAddress' => '172.31.0.5'],
                    ],
                ],
                ['InstanceId' => 'i-unmanaged', 'VpcId' => 'vpc-eni', 'PrivateIpAddress' => '172.31.0.6'],
            ],
        ]];

        $vpcs = [[
            'VpcId' => 'vpc-eni',
            'CidrBlock' => '172.31.0.0/16',
            'Tags' => [['Key' => 'Name', 'Value' => 'production']],
        ]];

        $networks = $provider->mapPrivateNetworks($reservations, $vpcs, ['i-123'], 'eu-west-2');

        $this->assertCount(1, $networks);
        $this->assertSame('vpc-eni', $networks[0]->externalId);
        $this->assertSame('production', $networks[0]->name);
        $this->assertSame('172.31.0.0/16', $networks[0]->cidr);
        $this->assertSame('eu-west-2', $networks[0]->region);
        $this->assertCount(1, $networks[0]->members);
        $this->assertSame('172.31.0.5', $networks[0]->members[0]->ip);
    }

    /**
     * An IPv6-only VPC carries its range in the association set and its instances have no
     * `PrivateIpAddress`. Reading only the IPv4 fields would sync the network with no range and
     * its members with no address, leaving them outside every firewall rule derived from it.
     */
    public function test_aws_mapper_reads_ipv6_only_vpcs_and_members(): void
    {
        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => AWS::id(),
            'credentials' => ['key' => 'k', 'secret' => 's'],
        ]);

        /** @var AWS $provider */
        $provider = $connection->provider();

        $networks = $provider->mapPrivateNetworks(
            [['Instances' => [[
                'InstanceId' => 'i-v6',
                'NetworkInterfaces' => [[
                    'VpcId' => 'vpc-v6',
                    'Ipv6Addresses' => [['Ipv6Address' => '2001:db8:1::5']],
                ]],
            ]]]],
            [[
                'VpcId' => 'vpc-v6',
                'Ipv6CidrBlockAssociationSet' => [['Ipv6CidrBlock' => '2001:db8:1::/56']],
            ]],
            ['i-v6'],
            'eu-west-1',
        );

        $this->assertCount(1, $networks);
        $this->assertSame('2001:db8:1::/56', $networks[0]->cidr);
        $this->assertSame('2001:db8:1::5', $networks[0]->members[0]->ip);
    }

    /**
     * A dual-stack VPC keeps its IPv4 identity — a network holds one range, and the members
     * report their IPv4 addresses.
     */
    public function test_aws_mapper_prefers_ipv4_on_a_dual_stack_vpc(): void
    {
        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => AWS::id(),
            'credentials' => ['key' => 'k', 'secret' => 's'],
        ]);

        /** @var AWS $provider */
        $provider = $connection->provider();

        $networks = $provider->mapPrivateNetworks(
            [['Instances' => [[
                'InstanceId' => 'i-dual',
                'NetworkInterfaces' => [[
                    'VpcId' => 'vpc-dual',
                    'PrivateIpAddress' => '172.31.0.5',
                    'Ipv6Addresses' => [['Ipv6Address' => '2001:db8:2::5']],
                ]],
            ]]]],
            [[
                'VpcId' => 'vpc-dual',
                'CidrBlock' => '172.31.0.0/16',
                'Ipv6CidrBlockAssociationSet' => [['Ipv6CidrBlock' => '2001:db8:2::/56']],
            ]],
            ['i-dual'],
            'eu-west-1',
        );

        $this->assertSame('172.31.0.0/16', $networks[0]->cidr);
        $this->assertSame('172.31.0.5', $networks[0]->members[0]->ip);
    }

    public function test_aws_mapper_falls_back_to_vpc_id_when_untagged(): void
    {
        $connection = ServerProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => AWS::id(),
            'credentials' => ['key' => 'k', 'secret' => 's'],
        ]);

        /** @var AWS $provider */
        $provider = $connection->provider();

        $networks = $provider->mapPrivateNetworks(
            [['Instances' => [['InstanceId' => 'i-1', 'VpcId' => 'vpc-9', 'PrivateIpAddress' => '10.0.0.2']]]],
            [['VpcId' => 'vpc-9', 'CidrBlock' => '10.0.0.0/16']],
            ['i-1'],
            'us-east-1',
        );

        $this->assertSame('vpc-9', $networks[0]->name);
    }

    /**
     * @return array<string, array{0: ?string}>
     */
    public static function untrustedAddresses(): array
    {
        return [
            'command substitution' => ['10.0.0.2 $(id)'],
            'command chaining' => ['10.0.0.2; rm -rf /'],
            'backticks' => ['`whoami`'],
            'newline injection' => ["10.0.0.2\nsudo ufw disable"],
            'ipv6 with metacharacters' => ['fd00::1; id'],
            'ipv6 with prefix' => ['fd00::1/64'],
            'not an address' => ['not-an-ip'],
            'empty' => [''],
        ];
    }

    #[DataProvider('untrustedAddresses')]
    public function test_member_addresses_that_are_not_literal_addresses_are_dropped(?string $address): void
    {
        Http::fake([
            'api.hetzner.cloud/v1/networks*' => Http::response([
                'networks' => [['id' => 1, 'name' => 'n', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
            'api.hetzner.cloud/v1/servers*' => Http::response([
                'servers' => [['id' => 101, 'private_net' => [['network' => 1, 'ip' => $address]]]],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
        ]);

        /** @var Hetzner $provider */
        $provider = $this->hetznerConnection()->provider();

        $networks = $provider->privateNetworks(['101'], []);

        $this->assertNull(
            $networks[0]->members[0]->ip,
            'Member addresses reach an unquoted shell interpolation in the ufw template.'
        );
    }

    public function test_valid_member_address_survives_normalisation(): void
    {
        Http::fake([
            'api.hetzner.cloud/v1/networks*' => Http::response([
                'networks' => [['id' => 1, 'name' => 'n', 'ip_range' => '10.0.0.0/16', 'servers' => [101]]],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
            'api.hetzner.cloud/v1/servers*' => Http::response([
                'servers' => [['id' => 101, 'private_net' => [['network' => 1, 'ip' => '10.0.0.42']]]],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
        ]);

        /** @var Hetzner $provider */
        $provider = $this->hetznerConnection()->provider();

        $this->assertSame('10.0.0.42', $provider->privateNetworks(['101'], [])[0]->members[0]->ip);
    }

    /**
     * IPv6 ranges are supported and canonicalised like IPv4 ones; only ranges that are
     * malformed — no prefix, out-of-range prefix, or not an address — are dropped, because
     * they reach a shell template through the rules derived from them.
     */
    public function test_ipv6_ranges_are_kept_and_malformed_ranges_are_dropped(): void
    {
        Http::fake([
            'api.hetzner.cloud/v1/networks*' => Http::response([
                'networks' => [
                    ['id' => 1, 'name' => 'v6', 'ip_range' => 'fd00::/64', 'servers' => [101]],
                    ['id' => 2, 'name' => 'junk', 'ip_range' => 'not-a-cidr', 'servers' => [101]],
                    ['id' => 3, 'name' => 'noprefix', 'ip_range' => '10.0.0.0', 'servers' => [101]],
                    ['id' => 4, 'name' => 'ok', 'ip_range' => '10.5.0.0/16', 'servers' => [101]],
                    ['id' => 5, 'name' => 'v6noncanonical', 'ip_range' => 'fd00:1:2:3:4::5/48', 'servers' => [101]],
                    ['id' => 6, 'name' => 'v6overlong', 'ip_range' => 'fd00::/129', 'servers' => [101]],
                    ['id' => 7, 'name' => 'v4overlong', 'ip_range' => '10.0.0.0/33', 'servers' => [101]],
                ],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
            'api.hetzner.cloud/v1/servers*' => Http::response([
                'servers' => [['id' => 101, 'private_net' => []]],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
        ]);

        /** @var Hetzner $provider */
        $provider = $this->hetznerConnection()->provider();

        $cidrs = array_map(fn ($n): ?string => $n->cidr, $provider->privateNetworks(['101'], []));

        $this->assertSame(
            ['fd00::/64', null, null, '10.5.0.0/16', 'fd00:1:2::/48', null, null],
            $cidrs
        );
    }

    public function test_ipv6_member_address_survives_normalisation(): void
    {
        Http::fake([
            'api.hetzner.cloud/v1/networks*' => Http::response([
                'networks' => [['id' => 1, 'name' => 'n', 'ip_range' => 'fd00::/64', 'servers' => [101]]],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
            'api.hetzner.cloud/v1/servers*' => Http::response([
                'servers' => [['id' => 101, 'private_net' => [['network' => 1, 'ip' => 'fd00::5']]]],
                'meta' => ['pagination' => ['next_page' => null]],
            ]),
        ]);

        /** @var Hetzner $provider */
        $provider = $this->hetznerConnection()->provider();

        $networks = $provider->privateNetworks(['101'], []);

        $this->assertSame('fd00::5', $networks[0]->members[0]->ip);
    }
}
