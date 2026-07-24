<?php

namespace App\ServerProviders;

use App\DTOs\PrivateNetworkDTO;
use App\DTOs\PrivateNetworkMemberDTO;
use App\Enums\OperatingSystem;
use App\Exceptions\CouldNotConnectToProvider;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Aws\Ec2\Ec2Client;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AWS extends AbstractProvider implements ProvidesPrivateNetworks
{
    protected Ec2Client $ec2Client;

    public static function id(): string
    {
        return 'aws';
    }

    public function instanceIdKey(): string
    {
        return 'instance_id';
    }

    /**
     * EC2 is regional, so each region is queried with its own client. A region failure aborts
     * the whole connection rather than returning partial results: the caller only skips
     * pruning per connection, and returning a partial view would let it delete networks that
     * live in the region that failed.
     */
    public function privateNetworks(array $instanceIds, array $regions): array
    {
        $result = [];

        foreach ($regions as $region) {
            try {
                $client = $this->networkClient($region);

                $reservations = $client->describeInstances([
                    'Filters' => [
                        ['Name' => 'instance-id', 'Values' => array_values($instanceIds)],
                    ],
                ])->toArray()['Reservations'] ?? [];

                $vpcIds = $this->vpcIdsFrom($reservations);

                $vpcs = $vpcIds === [] ? [] : ($client->describeVpcs([
                    'Filters' => [
                        ['Name' => 'vpc-id', 'Values' => $vpcIds],
                    ],
                ])->toArray()['Vpcs'] ?? []);
            } catch (Throwable) {
                throw $this->syncError(null, $region);
            }

            foreach ($this->mapPrivateNetworks($reservations, $vpcs, $instanceIds, $region) as $network) {
                $result[] = $network;
            }
        }

        return $result;
    }

    /**
     * Public so it can be exercised without an EC2 client — the SDK has no HTTP-level fake
     * equivalent to `Http::fake()` in this codebase. Not part of the provider contract.
     *
     * @internal
     *
     * @param  array<int, array<string, mixed>>  $reservations
     * @param  array<int, array<string, mixed>>  $vpcs
     * @param  array<int, string>  $instanceIds
     * @return array<int, PrivateNetworkDTO>
     */
    public function mapPrivateNetworks(array $reservations, array $vpcs, array $instanceIds, ?string $region = null): array
    {
        $wanted = array_flip($instanceIds);
        $members = [];

        foreach ($this->instancesFrom($reservations) as $instance) {
            $instanceId = (string) ($instance['InstanceId'] ?? '');

            if ($instanceId === '' || ! isset($wanted[$instanceId])) {
                continue;
            }

            [$vpcId, $ip] = $this->placementOf($instance);

            if ($vpcId === null) {
                continue;
            }

            $members[$vpcId][] = new PrivateNetworkMemberDTO(instanceId: $instanceId, ip: $ip);
        }

        $result = [];

        foreach ($vpcs as $vpc) {
            $vpcId = (string) ($vpc['VpcId'] ?? '');

            if (! isset($members[$vpcId])) {
                continue;
            }

            $result[] = new PrivateNetworkDTO(
                externalId: $vpcId,
                name: $this->nameOf($vpc, $vpcId),
                cidr: isset($vpc['CidrBlock']) ? (string) $vpc['CidrBlock'] : null,
                region: $region,
                members: $members[$vpcId],
            );
        }

        return $result;
    }

    /**
     * Prefers the primary network interface: an instance with several ENIs reports only one of
     * them in the top-level VpcId/PrivateIpAddress fields, and both are optional.
     *
     * @param  array<string, mixed>  $instance
     * @return array{0: ?string, 1: ?string}
     */
    private function placementOf(array $instance): array
    {
        foreach ($instance['NetworkInterfaces'] ?? [] as $interface) {
            $vpcId = $interface['VpcId'] ?? null;
            $ip = $interface['PrivateIpAddress'] ?? null;

            if (is_string($vpcId) && $vpcId !== '') {
                return [$vpcId, is_string($ip) && $ip !== '' ? $ip : null];
            }
        }

        $vpcId = $instance['VpcId'] ?? null;
        $ip = $instance['PrivateIpAddress'] ?? null;

        return [
            is_string($vpcId) && $vpcId !== '' ? $vpcId : null,
            is_string($ip) && $ip !== '' ? $ip : null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $reservations
     * @return array<int, array<string, mixed>>
     */
    private function instancesFrom(array $reservations): array
    {
        $instances = [];

        foreach ($reservations as $reservation) {
            /** @var array<int, array<string, mixed>> $batch */
            $batch = $reservation['Instances'] ?? [];
            $instances = array_merge($instances, $batch);
        }

        return $instances;
    }

    /**
     * @param  array<int, array<string, mixed>>  $reservations
     * @return array<int, string>
     */
    private function vpcIdsFrom(array $reservations): array
    {
        $ids = [];

        foreach ($this->instancesFrom($reservations) as $instance) {
            [$vpcId] = $this->placementOf($instance);

            if ($vpcId !== null && ! in_array($vpcId, $ids, true)) {
                $ids[] = $vpcId;
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $vpc
     */
    private function nameOf(array $vpc, string $fallback): string
    {
        foreach ($vpc['Tags'] ?? [] as $tag) {
            if (($tag['Key'] ?? null) === 'Name' && is_string($tag['Value'] ?? null) && $tag['Value'] !== '') {
                return $tag['Value'];
            }
        }

        return $fallback;
    }

    private function networkClient(string $region): Ec2Client
    {
        $credentials = $this->serverProvider->getCredentials();

        return new Ec2Client([
            'region' => $region,
            'version' => '2016-11-15',
            'credentials' => [
                'key' => $credentials['key'],
                'secret' => $credentials['secret'],
            ],
        ]);
    }

    public function createRules(array $input): array
    {
        return [
            'plan' => ['required'],
            'region' => ['required'],
        ];
    }

    public function credentialValidationRules(array $input): array
    {
        return [
            'key' => 'required',
            'secret' => 'required',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'key' => $input['key'],
            'secret' => $input['secret'],
        ];
    }

    public function data(array $input): array
    {
        return [
            'plan' => $input['plan'],
            'region' => $input['region'],
        ];
    }

    /**
     * @throws CouldNotConnectToProvider
     */
    public function connect(?array $credentials = null): bool
    {
        try {
            $this->connectToEc2ClientTest($credentials ?? []);
            $this->ec2Client->describeInstances();

            return true;
        } catch (Exception) {
            throw new CouldNotConnectToProvider('AWS');
        }
    }

    public function plans(?string $region): array
    {
        $this->connectToEc2Client($region);

        $nextToken = null;
        $plans = [];

        do {
            $params = [
                'Filters' => [
                    [
                        'Name' => 'processor-info.supported-architecture',
                        'Values' => ['x86_64', 'arm64'], // Include both x86_64 and ARM64
                    ],
                    [
                        'Name' => 'current-generation',
                        'Values' => ['true'],
                    ],
                    [
                        'Name' => 'supported-virtualization-type',
                        'Values' => ['hvm'], // Ubuntu AMIs require HVM
                    ],
                    [
                        'Name' => 'bare-metal',
                        'Values' => ['false'], // Skip bare-metal unless explicitly needed
                    ],
                ],
            ];

            if ($nextToken) {
                $params['NextToken'] = $nextToken;
            }

            $result = $this->ec2Client->describeInstanceTypes($params);

            $plans = array_merge($plans, $result->get('InstanceTypes'));

            $nextToken = $result->get('NextToken');
        } while ($nextToken);

        return collect($plans)
            ->mapWithKeys(fn ($value) => [
                $value['InstanceType'] => __('server_providers.plan', [
                    'name' => $value['InstanceType'],
                    'cpu' => $value['VCpuInfo']['DefaultVCpus'] ?? 'N/A',
                    'memory' => $value['MemoryInfo']['SizeInMiB'] ?? 'N/A',
                    'disk' => $value['InstanceStorageInfo']['TotalSizeInGB'] ?? 'N/A',
                ]),
            ])
            ->toArray();
    }

    public function regions(): array
    {
        $this->connectToEc2Client();

        $regions = $this->ec2Client->describeRegions();

        /** @var array<int, array{RegionName: string}> $regionsArray */
        $regionsArray = $regions->toArray()['Regions'] ?? [];

        return collect($regionsArray)
            ->mapWithKeys(fn ($value) => [$value['RegionName'] => $value['RegionName']])
            ->toArray();
    }

    /**
     * @throws Exception
     */
    public function create(): void
    {
        $this->connectToEc2Client();
        $this->createKeyPair();
        $this->createSecurityGroup();
        $this->runInstance();
    }

    public function isRunning(): bool
    {
        $this->connectToEc2Client();
        $result = $this->ec2Client->describeInstances([
            'InstanceIds' => [$this->server->provider_data['instance_id']],
        ]);

        if (count($result['Reservations'][0]['Instances']) == 1) {
            if (! $this->server->ip && isset($result['Reservations'][0]['Instances'][0]['PublicIpAddress'])) {
                $this->server->ip = $result['Reservations'][0]['Instances'][0]['PublicIpAddress'];
                $this->server->save();
            }

            if (! $this->server->ip) {
                return false;
            }

            if (isset($result['Reservations'][0]['Instances'][0]['State']) && isset($result['Reservations'][0]['Instances'][0]['State']['Name'])) {
                $status = $result['Reservations'][0]['Instances'][0]['State']['Name'];
                if ($status == 'running') {
                    return true;
                }
            }
        }

        return false;
    }

    public function delete(): void
    {
        if (isset($this->server->provider_data['instance_id'])) {
            try {
                $this->connectToEc2Client();
                $this->ec2Client->terminateInstances([
                    'InstanceIds' => [$this->server->provider_data['instance_id']],
                ]);
            } catch (Throwable) {
                Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));
            }
        }
    }

    private function connectToEc2Client(?string $region = null): void
    {
        $credentials = $this->serverProvider->getCredentials();

        if ($region === null || $region === '' || $region === '0') {
            $region = $this->server->provider_data['region'] ?? null;
        }

        $this->ec2Client = new Ec2Client([
            'region' => $region ?? config('serverproviders.aws.regions')[0]['value'],
            'version' => '2016-11-15',
            'credentials' => [
                'key' => $credentials['key'],
                'secret' => $credentials['secret'],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function connectToEc2ClientTest(array $credentials): void
    {
        $this->ec2Client = new Ec2Client([
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => [
                'key' => $credentials['key'],
                'secret' => $credentials['secret'],
            ],
        ]);
    }

    private function createKeyPair(): void
    {
        $keyName = $this->server->name.'-'.$this->server->id;
        $result = $this->ec2Client->createKeyPair([
            'KeyName' => $keyName,
        ]);
        /** @var FilesystemAdapter $storageDisk */
        $storageDisk = Storage::disk(config('core.key_pairs_disk'));
        $storageDisk->put((string) $this->server->id, $result['KeyMaterial']);
        generate_public_key(
            $storageDisk->path((string) $this->server->id),
            $storageDisk->path($this->server->id.'.pub'),
        );
    }

    private function createSecurityGroup(): void
    {
        $groupName = $this->server->name.'-'.$this->server->id;
        $result = $this->ec2Client->createSecurityGroup([
            'GroupId' => $groupName,
            'GroupName' => $groupName,
            'Description' => $groupName,
        ]);
        $groupId = $result->get('GroupId');
        $this->ec2Client->authorizeSecurityGroupIngress([
            'GroupName' => $groupName,
            'GroupId' => $groupId,
            'IpPermissions' => [
                [
                    'IpProtocol' => '-1',
                    'FromPort' => 0,
                    'ToPort' => 65535,
                    'IpRanges' => [
                        ['CidrIp' => '0.0.0.0/0'],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    private function runInstance(): void
    {
        $keyName = $groupName = $this->server->name.'-'.$this->server->id;
        $result = $this->ec2Client->runInstances([
            'ImageId' => $this->getImageId($this->server->os),
            'MinCount' => 1,
            'MaxCount' => 1,
            'InstanceType' => $this->server->provider_data['plan'],
            'KeyName' => $keyName,
            'SecurityGroupIds' => [$groupName],
        ]);
        $this->server->local_ip = $result['Instances'][0]['PrivateIpAddress'];
        $providerData = $this->server->provider_data;
        $providerData['instance_id'] = $result['Instances'][0]['InstanceId'];
        $providerData['zone'] = $result['Instances'][0]['Placement']['AvailabilityZone'];
        $this->server->provider_data = $providerData;
        $this->server->save();
    }

    /**
     * @throws Exception
     */
    private function getImageId(OperatingSystem $os): string
    {
        $this->connectToEc2Client();

        $version = $os->getVersion();

        $result = $this->ec2Client->describeImages([
            'Filters' => [
                [
                    'Name' => 'name',
                    'Values' => ['ubuntu/images/*-'.$version.'-amd64-server-*'],
                ],
                [
                    'Name' => 'state',
                    'Values' => ['available'],
                ],
                [
                    'Name' => 'virtualization-type',
                    'Values' => ['hvm'],
                ],
            ],
            'Owners' => ['099720109477'],
        ]);

        // Extract and display image information
        $images = $result->get('Images');

        if (! empty($images)) {
            // Sort images by creation date to get the latest one
            usort($images, fn (array $a, array $b): int => strtotime((string) $b['CreationDate']) - strtotime((string) $a['CreationDate']));

            return $images[0]['ImageId'];
        }

        throw new Exception('Could not find image ID');
    }
}
