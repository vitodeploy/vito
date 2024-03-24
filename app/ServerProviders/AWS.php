<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Aws\Ec2\Ec2Client;
use Aws\EC2InstanceConnect\EC2InstanceConnectClient;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class AWS extends AbstractProvider
{
    protected Ec2Client $ec2Client;

    protected EC2InstanceConnectClient $ec2InstanceConnectClient;

    public function createRules(array $input): array
    {
        $rules = [
            'os' => [
                'required',
                Rule::in(config('core.operating_systems')),
            ],
        ];
        // plans
        $plans = [];
        foreach (config('serverproviders.aws.plans') as $plan) {
            $plans[] = $plan['value'];
        }
        $rules['plan'] = 'required|in:'.implode(',', $plans);
        // regions
        $regions = [];
        foreach (config('serverproviders.aws.regions') as $region) {
            $regions[] = $region['value'];
        }
        $rules['region'] = 'required|in:'.implode(',', $regions);

        return $rules;
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
            $this->connectToEc2ClientTest($credentials);
            $this->ec2Client->describeInstances();

            return true;
        } catch (Exception) {
            throw new CouldNotConnectToProvider('AWS');
        }
    }

    public function plans(): array
    {
        return config('serverproviders.aws.plans');
    }

    public function regions(): array
    {
        return config('serverproviders.aws.regions');
    }

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

    private function connectToEc2Client(): void
    {
        $credentials = $this->server->serverProvider->getCredentials();

        $this->ec2Client = new Ec2Client([
            'region' => $this->server->provider_data['region'],
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

    private function runInstance(): void
    {
        $keyName = $groupName = $this->server->name.'-'.$this->server->id;
        $result = $this->ec2Client->runInstances([
            'ImageId' => config('serverproviders.aws.images.'.$this->server->provider_data['region'].'.'.$this->server->os),
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
}
