<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Aws\Ec2\Ec2Client;
use Aws\Ec2\Exception\Ec2Exception;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AWS extends AbstractProvider
{
    protected Ec2Client $ec2Client;

    public function createRules(array $input): array
    {
        return [
            'plan' => ['required'],
            'region' => ['required'],
            'zone' => ['required'],
            'os' => ['required'],
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
            'zone' => $input['zone'],
            'os' => $input['os'],
        ];
    }

    /**
     * @throws CouldNotConnectToProvider
     */
    public function connect(?array $credentials = null): bool
    {
        try {
            $this->connectToEc2ClientTest($credentials);
            $this->ec2Client->describeInstances();

            return true;
        } catch (Exception) {
            throw new CouldNotConnectToProvider('AWS');
        }
    }

    public function plans(?string $region, ?string $zone = null): array
    {
        try {
            $this->connectToEc2Client($region);

            $instanceTypes = [
                // General purpose:
                'm1.*',
                'm2.*',
                'm3.*',
                'm4.*',
                'm5.*',
                'm5a.*',
                'm5ad.*',
                'm5d.*',
                'm5dn.*',
                'm5n.*',
                'm5zn.*',
                't1.*',
                't2.*',
                't3.*',
                't3a.*',
                't4g.*',
                // Compute optimized:
                'c1.*',
                'c3.*',
                'c4.*',
                'c5.*',
                'c5a.*',
                'c5ad.*',
                'c5d.*',
                'c5n.*',
                'c6a.*',
                'c6g.*',
                // Memory optimized:
                'r3.*',
                'r4.*',
                'r5.*',
                'r5a.*',
                'x1.*',
                // Storage optimized:
                'd2.*',
                'd3.*',
                'd3en.*',
                'h1.*',
                'i2.*',
                'i3.*',
                'i3en.*',
                'i4g.*',
                'i4i.*',
            ];
    
            $instanceTypeSizes = [
                '*.nano',
                '*.micro',
                '*.small',
                '*.medium',
                '*.large',
                '*.2xlarge',
                '*.3xlarge',
                '*.4xlarge',
                '*.5xlarge',
                '*.6xlarge',
                '*.7xlarge',
                '*.8xlarge',
                '*.9xlarge',
            ];

            // Get all available instance types on region's zone
            $instanceTypeOfferingsPaginator = $this->ec2Client->getPaginator('DescribeInstanceTypeOfferings', [
                'Filters' => [
                    [
                        'Name' => 'location',
                        'Values' => [$zone],
                    ],
                    [
                        'Name' => 'instance-type',
                        'Values' => $instanceTypes,
                    ],
                    [
                        'Name' => 'instance-type',
                        'Values' => $instanceTypeSizes,
                    ],
                ],
                'LocationType' => 'availability-zone',
                'MaxResults' => 100,
            ]);

            // Iterate through each page of results
            $instanceTypeOfferings = collect($instanceTypeOfferingsPaginator)
                ->flatMap(fn ($page) => $page['InstanceTypeOfferings'])
                ->pluck('InstanceType')
                ->all();

            $instanceTypesPaginator = $this->ec2Client->getPaginator('DescribeInstanceTypes', [
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
                    [
                        'Name' => 'instance-type',
                        'Values' => $instanceTypeOfferings, // Include only region's zone available instance type
                    ],
                ],
                'MaxResults' => 100,
            ]);

            // Iterate through each page of results
            $plans = collect($instanceTypesPaginator)
                ->flatMap(fn ($page) => $page['InstanceTypes'])
                ->all();

            return collect($plans)
                ->sortBy('MemoryInfo.SizeInMiB')
                ->mapWithKeys(fn ($value) => [
                    $value['InstanceType'] => __('server_providers.plan', [
                        'name' => $value['InstanceType'],
                        'cpu' => $value['VCpuInfo']['DefaultVCpus'] ?? 'N/A',
                        'memory' => isset($value['MemoryInfo']) ? round($value['MemoryInfo']['SizeInMiB'] / 1024, 2) : 'N/A',
                        'disk' => isset($value['InstanceStorageInfo']) ? $value['InstanceStorageInfo']['TotalSizeInGB'] : 'N/A',
                    ]),
                ])
                ->toArray();
        } catch (Ec2Exception) {
            return [];
        }
    }

    public function zones(?string $region): array
    {
        try {
            $this->connectToEc2Client($region);

            $zones = $this->ec2Client->describeAvailabilityZones([
                'RegionName' => $region ?? 'us-east-1',
                'State' => 'available',
            ])->toArray();

            return collect($zones['AvailabilityZones'] ?? [])
                ->mapWithKeys(fn ($value) => [$value['ZoneName'] => $value['ZoneName']])
                ->toArray();
        } catch (Ec2Exception) {
            return [];
        }
    }

    public function regions(): array
    {
        try {
            $this->connectToEc2Client();

            $regions = $this->ec2Client->describeRegions()->toArray();

            return collect($regions['Regions'] ?? [])
                ->sortBy('RegionName')
                ->mapWithKeys(fn ($value) => [$value['RegionName'] => $value['RegionName']])
                ->toArray();
        } catch (Ec2Exception) {
            return [];
        }
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

        if (! $region) {
            $region = $this->server?->provider_data['region'];
        }

        $this->ec2Client = new Ec2Client([
            'region' => $region ?? 'us-east-1',
            'version' => '2016-11-15',
            'credentials' => [
                'key' => $credentials['key'],
                'secret' => $credentials['secret'],
            ],
        ]);
    }

    private function connectToEc2ClientTest($credentials): void
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
    private function getImageId(string $os): string
    {
        $version = config('core.operating_system_versions.'.$os);

        if (Cache::get('aws-image-'.$os.'-'.$version)) {
            return Cache::get('aws-image-'.$os.'-'.$version);
        }

        $this->connectToEc2Client();

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
            usort($images, function ($a, $b) {
                return strtotime($b['CreationDate']) - strtotime($a['CreationDate']);
            });

            Cache::put('aws-image-'.$os.'-'.$version, $images[0]['ImageId'], 600);
            return $images[0]['ImageId'];
        }

        throw new Exception('Could not find image ID for '.$os);
    }
}
