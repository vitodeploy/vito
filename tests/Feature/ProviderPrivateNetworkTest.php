<?php

use App\Exceptions\PrivateNetworkSyncError;
use App\Models\ServerProvider;
use App\ServerProviders\AWS;
use App\ServerProviders\DigitalOcean;
use App\ServerProviders\Hetzner;
use App\ServerProviders\Linode;
use App\ServerProviders\Vultr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function vitoPestFeatureProviderPrivateNetworkTestHetznerConnection(): ServerProvider
{
    return ServerProvider::factory()->create([
        'user_id' => test()->user->id,
        'provider' => Hetzner::id(),
        'profile' => 'hetzner-main',
        'credentials' => ['token' => 'secret-token'],
    ]);
}

test('hetzner maps networks and member ips', function () {
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
    $provider = vitoPestFeatureProviderPrivateNetworkTestHetznerConnection()->provider();

    $networks = $provider->privateNetworks(['101', '102'], []);

    expect($networks)->toHaveCount(1);
    expect($networks[0]->externalId)->toBe('4711');
    expect($networks[0]->name)->toBe('prod-net');
    expect($networks[0]->cidr)->toBe('10.0.0.0/16');
    expect($networks[0]->region)->toBe('eu-central');

    expect($networks[0]->members)->toHaveCount(2, 'Instances Vito does not manage must be ignored.');
    expect(array_map(fn ($m): string => $m->instanceId, $networks[0]->members))->toBe(['101', '102']);
    expect(array_map(fn ($m): ?string => $m->ip, $networks[0]->members))->toBe(['10.0.0.2', '10.0.0.3']);
});

test('hetzner skips networks with no managed members', function () {
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
    $provider = vitoPestFeatureProviderPrivateNetworkTestHetznerConnection()->provider();

    expect($provider->privateNetworks(['101'], []))->toBe([]);
});

test('hetzner follows pagination', function () {
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
    $provider = vitoPestFeatureProviderPrivateNetworkTestHetznerConnection()->provider();

    $networks = $provider->privateNetworks(['101'], []);

    expect($networks)->toHaveCount(1);
    expect($networks[0]->externalId)->toBe('2');
    expect($networks[0]->members[0]->ip)->toBe('10.2.0.4');
});

test('hetzner error does not leak credentials', function () {
    Http::fake([
        'api.hetzner.cloud/v1/*' => Http::response(['error' => ['message' => 'forbidden']], 403),
    ]);

    /** @var Hetzner $provider */
    $provider = vitoPestFeatureProviderPrivateNetworkTestHetznerConnection()->provider();

    try {
        $provider->privateNetworks(['101'], []);
        $this->fail('Expected PrivateNetworkSyncError.');
    } catch (PrivateNetworkSyncError $e) {
        expect($e->isPermissionError())->toBeTrue();
        $this->assertStringNotContainsString('secret-token', $e->getMessage());
        $this->assertStringNotContainsString('secret-token', (string) $e);
        expect($e->provider)->toBe('hetzner');
        expect($e->profile)->toBe('hetzner-main');
    }
});

test('digitalocean uses droplet vpc uuid and private address', function () {
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

    expect($networks)->toHaveCount(1);
    expect($networks[0]->externalId)->toBe('vpc-abc');
    expect($networks[0]->cidr)->toBe('10.106.0.0/20');
    expect($networks[0]->members)->toHaveCount(1, 'Unmanaged droplets must be excluded.');
    expect($networks[0]->members[0]->ip)->toBe('10.106.0.4');
});

test('linode reads membership from subnets and ips from account list', function () {
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

    expect($networks)->toHaveCount(1);
    expect($networks[0]->name)->toBe('prod-vpc');
    expect($networks[0]->cidr)->toBe('10.0.1.0/24');
    expect($networks[0]->members)->toHaveCount(1);
    expect($networks[0]->members[0]->ip)->toBe('10.0.1.4');
});

test('linode stops and fails on a runaway page count', function () {
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
});

test('linode multi subnet vpc reports no single cidr', function () {
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

    expect($networks[0]->cidr)->toBeNull('A VPC with several subnets has no single CIDR; firewall rules fall back to member /32s.');
});

test('vultr builds cidr from v4 subnet and mask', function () {
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

    expect($networks)->toHaveCount(1);
    expect($networks[0]->name)->toBe('prod-vpc');
    expect($networks[0]->cidr)->toBe('10.20.0.0/24');
    expect($networks[0]->members[0]->ip)->toBe('10.20.0.4');
});

test('vultr stale instance id does not abort the connection', function () {
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

    expect($networks)->toHaveCount(1, 'A deleted instance must not abort the whole connection.');
    expect($networks[0]->members[0]->ip)->toBe('10.20.0.4');
    expect($networks[0]->members[0]->instanceId)->toBe('live');
});

test('vultr non 404 error still fails the connection', function () {
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
});

test('aws mapper prefers network interface and name tag', function () {
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

    expect($networks)->toHaveCount(1);
    expect($networks[0]->externalId)->toBe('vpc-eni');
    expect($networks[0]->name)->toBe('production');
    expect($networks[0]->cidr)->toBe('172.31.0.0/16');
    expect($networks[0]->region)->toBe('eu-west-2');
    expect($networks[0]->members)->toHaveCount(1);
    expect($networks[0]->members[0]->ip)->toBe('172.31.0.5');
});

test('aws mapper reads ipv6 only vpcs and members', function () {
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

    expect($networks)->toHaveCount(1);
    expect($networks[0]->cidr)->toBe('2001:db8:1::/56');
    expect($networks[0]->members[0]->ip)->toBe('2001:db8:1::5');
});

test('aws mapper prefers ipv4 on a dual stack vpc', function () {
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

    expect($networks[0]->cidr)->toBe('172.31.0.0/16');
    expect($networks[0]->members[0]->ip)->toBe('172.31.0.5');
});

test('aws mapper falls back to vpc id when untagged', function () {
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

    expect($networks[0]->name)->toBe('vpc-9');
});

dataset('untrustedAddresses', /** @return array<string, array{0: string}> */ function (): array {
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
});

test('member addresses that are not literal addresses are dropped', function (?string $address) {
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
    $provider = vitoPestFeatureProviderPrivateNetworkTestHetznerConnection()->provider();

    $networks = $provider->privateNetworks(['101'], []);

    expect($networks[0]->members[0]->ip)->toBeNull('Member addresses reach an unquoted shell interpolation in the ufw template.');
})->with('untrustedAddresses');

test('valid member address survives normalisation', function () {
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
    $provider = vitoPestFeatureProviderPrivateNetworkTestHetznerConnection()->provider();

    expect($provider->privateNetworks(['101'], [])[0]->members[0]->ip)->toBe('10.0.0.42');
});

test('ipv6 ranges are kept and malformed ranges are dropped', function () {
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
    $provider = vitoPestFeatureProviderPrivateNetworkTestHetznerConnection()->provider();

    $cidrs = array_map(fn ($n): ?string => $n->cidr, $provider->privateNetworks(['101'], []));

    expect($cidrs)->toBe(['fd00::/64', null, null, '10.5.0.0/16', 'fd00:1:2::/48', null, null]);
});

test('ipv6 member address survives normalisation', function () {
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
    $provider = vitoPestFeatureProviderPrivateNetworkTestHetznerConnection()->provider();

    $networks = $provider->privateNetworks(['101'], []);

    expect($networks[0]->members[0]->ip)->toBe('fd00::5');
});
