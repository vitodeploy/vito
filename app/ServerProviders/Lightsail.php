<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Aws\Lightsail\Exception\LightsailException;
use Aws\Lightsail\LightsailClient;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Lightsail extends AbstractProvider
{
    protected LightsailClient $lightsailClient;

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
            $this->connectToLightsailClientTest($credentials);
            $this->lightsailClient->getRegions(); // For testing the connection is success or fail.

            return true;
        } catch (Exception) {
            throw new CouldNotConnectToProvider('Lightsail');
        }
    }

    public function plans(?string $region, ?string $zone = null): array
    {
        try {
            $this->connectToLightsailClient($region);

            $bundles = $this->lightsailClient->getBundles([
                'includeInactive' => false,
            ])->toArray();

            $plans = collect($bundles['bundles'])
                ->filter(fn ($bundle) => (($bundle['supportedPlatforms'][0] == 'LINUX_UNIX') && $bundle['publicIpv4AddressCount'] > 0))
                ->values();

            return collect($plans)
                ->mapWithKeys(function ($value) {
                    return [
                        $value['bundleId'] => __('server_providers.plan', [
                            'name' => $value['name'],
                            'cpu' => $value['cpuCount'],
                            'memory' => $value['ramSizeInGb'],
                            'disk' => $value['diskSizeInGb'],
                        ]),
                    ];
                })
                ->toArray();
        } catch (LightsailException) {
            return [];
        }
    }

    public function zones(?string $region): array
    {
        try {
            $this->connectToLightsailClient($region);

            $regions = $this->lightsailClient->getRegions([
                'includeAvailabilityZones' => true,
            ])->toArray();

            $zones = collect($regions['regions'])->filter(function ($zone) use ($region) {
                return $zone['name'] == $region;
            })->values();

            return collect($zones[0]['availabilityZones'])
                ->filter(fn ($availableZone) => $availableZone['state'] == 'available')
                ->mapWithKeys(fn ($value) => [$value['zoneName'] => $value['zoneName']])
                ->toArray();
        } catch (LightsailException) {
            return [];
        }
    }

    public function regions(): array
    {
        try {
            $this->connectToLightsailClient();

            $regions = $this->lightsailClient->getRegions([])->toArray();

            return collect($regions['regions'])
                ->sortByDesc('continentCode')
                ->mapWithKeys(fn ($region) => [$region['name'] => $region['displayName'].', '.$region['continentCode'].' ('.$region['name'].')'])
                ->toArray();
        } catch (LightsailException) {
            return [];
        }
    }

    public function create(): void
    {
        $this->connectToLightsailClient();
        $this->createKeyPair();
        $this->createInstance();
    }

    private function connectToLightsailClient(?string $region = null): void
    {
        $credentials = $this->serverProvider->getCredentials();

        if (! $region) {
            $region = $this->server?->provider_data['region'];
        }

        $this->lightsailClient = new LightsailClient([
            'region' => $region ?? 'us-east-1',
            'version' => '2016-11-28',
            'credentials' => [
                'key' => $credentials['key'],
                'secret' => $credentials['secret'],
            ],
        ]);
    }

    private function connectToLightsailClientTest($credentials): void
    {
        $this->lightsailClient = new LightsailClient([
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
        try {
            $this->generateKeyPair();

            $createSshKey = $this->lightsailClient->importKeyPair([
                'keyPairName' => str($this->server->name)->slug().'-'.$this->server->id.'-key',
                'publicKeyBase64' => $this->server->sshKey()['public_key'],
            ])->toArray();

            $this->server->jsonUpdate('provider_data', 'ssh_key_id', $createSshKey['operation']['resourceName']);
        } catch (LightsailException $e) {
            throw new ServerProviderError($e->getAwsErrorMessage());
        }
    }

    private function openInstancePublicPorts(): void
    {
        try {
            $result = $this->lightsailClient->openInstancePublicPorts([
                'instanceName' => $this->server->provider_data['instance_id'],
                'portInfo' => [
                    'cidrs' => ['0.0.0.0/0'],
                    'fromPort' => 0,
                    'ipv6Cidrs' => ['::/0'],
                    'protocol' => 'tcp',
                    'toPort' => 65535,
                ],
            ])->toArray();

            $this->server->jsonUpdate('provider_data', 'security_group_id', $result['operation']['resourceName']);
        } catch (LightsailException $e) {
            throw new ServerProviderError($e->getAwsErrorMessage());
        }
    }

    private function getImageId(string $os): string
    {
        $version = config('core.operating_system_versions.'.$os);

        if (Cache::get('lightsail-image-'.$os.'-'.$version)) {
            return Cache::get('lightsail-image-'.$os.'-'.$version);
        }

        try {
            $this->connectToLightsailClient();

            $result = $this->lightsailClient->getBlueprints([
                'includeInactive' => false,
            ])->toArray();

            $image = collect($result['blueprints'])
                ->filter(function ($blueprint) use ($version) {
                    return Str::lower($blueprint['name']) == 'ubuntu' && Str::contains($blueprint['version'], $version);
                })
                ->where('platform', 'LINUX_UNIX')
                ->first();

            if (! empty($image)) {
                Cache::put('lightsail-image-'.$os.'-'.$version, $image['blueprintId'], 600);

                return $image['blueprintId'];
            }

            throw new ServerProviderError('Could not find image ID for '.$os);
        } catch (LightsailException $e) {
            throw new ServerProviderError($e->getAwsErrorMessage());
        }
    }

    private function createInstance(): void
    {
        try {
            $instanceName = str($this->server->name)->slug().'-'.$this->server->id;

            $result = $this->lightsailClient->createInstances([
                'availabilityZone' => $this->server->provider_data['zone'],
                'blueprintId' => $this->getImageId($this->server->os),
                'bundleId' => $this->server->provider_data['plan'],
                'instanceNames' => [$instanceName],
                'ipAddressType' => 'dualstack',
                'keyPairName' => $this->server->provider_data['ssh_key_id'],
            ])->toArray();

            $this->server->jsonUpdate('provider_data', 'instance_id', $result['operations'][0]['resourceName']);
        } catch (LightsailException $e) {
            $message = __('Failed to create instance on Lightsail');
            Log::error($message, $e->toArray());

            throw new ServerProviderError($message.': '.$e->getAwsErrorMessage());
        }
    }

    public function isRunning(): bool
    {
        try {
            $this->connectToLightsailClient();

            $result = $this->lightsailClient->getInstance([
                'instanceName' => $this->server->provider_data['instance_id'],
            ])->toArray();

            if (isset($result['instance']['state']['name'])) {
                if (isset($result['instance']['ipv6Addresses'])) {
                    $this->server->jsonUpdate('provider_data', 'public_ip_v6', $result['instance']['ipv6Addresses'][0], false);
                }

                if (! $this->server->local_ip && isset($result['instance']['privateIpAddress'])) {
                    $this->server->local_ip = $result['instance']['privateIpAddress'];
                }

                if (! $this->server->ip && isset($result['instance']['publicIpAddress'])) {
                    $this->server->ip = $result['instance']['publicIpAddress'];
                }

                $this->server->ssh_user = $result['instance']['username'];
                $this->server->save();

                if ($result['instance']['state']['name'] == 'running') {
                    if (! isset($this->server->provider_data['security_group_id'])) {
                        // Lightsail networking available after instance is running
                        $this->openInstancePublicPorts();
                    }

                    return true;
                }
            }

            return false;
        } catch (LightsailException $e) {
            $message = __('Failed to get instance status on Lightsail');
            Log::error($message, $e->toArray());

            return false;
        }
    }

    public function delete(): void
    {
        if (isset($this->server->provider_data['instance_id'])) {
            try {
                $this->connectToLightsailClient();

                $delete = $this->lightsailClient->deleteInstance([
                    'forceDeleteAddOns' => true,
                    'instanceName' => $this->server->provider_data['instance_id'],
                ])->toArray();

                if ($delete['operations'][0]['status'] != 'Succeeded') {
                    Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));

                    return;
                }
            } catch (LightsailException $e) {
                Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));
            }
        }

        // Delete SSH Key
        if (isset($this->server->provider_data['ssh_key_id'])) {
            try {
                $this->connectToLightsailClient();

                $this->lightsailClient->deleteKeyPair([
                    'keyPairName' => $this->server->provider_data['ssh_key_id'],
                ]);
            } catch (LightsailException $e) {
                throw new ServerProviderError($e->getAwsErrorMessage());
            }
        }
    }
}
