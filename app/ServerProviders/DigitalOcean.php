<?php

namespace App\ServerProviders;

use App\DTOs\PrivateNetworkDTO;
use App\DTOs\PrivateNetworkMemberDTO;
use App\Enums\OperatingSystem;
use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\PrivateNetworkSyncError;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigitalOcean extends AbstractProvider implements ProvidesPrivateNetworks
{
    protected string $apiUrl = 'https://api.digitalocean.com/v2';

    private const MAX_PAGES = 100;

    public static function id(): string
    {
        return 'digitalocean';
    }

    public function instanceIdKey(): string
    {
        return 'droplet_id';
    }

    public function privateNetworks(array $instanceIds, array $regions): array
    {
        return $this->mapPrivateNetworks(
            $this->fetchAll('/vpcs', 'vpcs'),
            $this->fetchAll('/droplets', 'droplets'),
            $instanceIds,
        );
    }

    /**
     * Droplet objects carry `vpc_uuid` and their private `networks.v4` entry, so membership
     * and addresses come from one list call. `/vpcs/{id}/members` returns URNs with no IP and
     * would need a fetch per droplet.
     *
     * @param  array<int, array<string, mixed>>  $vpcs
     * @param  array<int, array<string, mixed>>  $droplets
     * @param  array<int, string>  $instanceIds
     * @return array<int, PrivateNetworkDTO>
     */
    private function mapPrivateNetworks(array $vpcs, array $droplets, array $instanceIds): array
    {
        $wanted = array_flip($instanceIds);
        $members = [];

        foreach ($droplets as $droplet) {
            $dropletId = (string) ($droplet['id'] ?? '');
            $vpcId = $droplet['vpc_uuid'] ?? null;

            if (! isset($wanted[$dropletId]) || ! is_string($vpcId) || $vpcId === '') {
                continue;
            }

            $ip = null;

            foreach ($droplet['networks']['v4'] ?? [] as $address) {
                if (($address['type'] ?? null) === 'private' && isset($address['ip_address'])) {
                    $ip = (string) $address['ip_address'];

                    break;
                }
            }

            $members[$vpcId][] = new PrivateNetworkMemberDTO(instanceId: $dropletId, ip: $ip);
        }

        $result = [];

        foreach ($vpcs as $vpc) {
            $vpcId = (string) ($vpc['id'] ?? '');

            if (! isset($members[$vpcId])) {
                continue;
            }

            $result[] = new PrivateNetworkDTO(
                externalId: $vpcId,
                name: (string) ($vpc['name'] ?? $vpcId),
                cidr: isset($vpc['ip_range']) ? (string) $vpc['ip_range'] : null,
                region: isset($vpc['region']) ? (string) $vpc['region'] : null,
                members: $members[$vpcId],
            );
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws PrivateNetworkSyncError
     */
    private function fetchAll(string $path, string $key): array
    {
        $token = $this->serverProvider->getCredentials()['token'];
        $items = [];
        $page = 1;

        do {
            try {
                $response = Http::withToken($token)->get($this->apiUrl.$path, [
                    'per_page' => 200,
                    'page' => $page,
                ]);
            } catch (Exception) {
                throw $this->syncError();
            }

            if (! $response->ok()) {
                throw $this->syncError($response->status());
            }

            $body = $response->json();

            if (! is_array($body) || ! is_array($body[$key] ?? null)) {
                throw $this->syncError($response->status());
            }

            /** @var array<int, array<string, mixed>> $batch */
            $batch = $body[$key];
            $items = array_merge($items, $batch);

            if (! isset($body['links']['pages']['next'])) {
                break;
            }

            if ($page >= self::MAX_PAGES) {
                throw $this->syncError();
            }

            $page++;
        } while (true);

        return $items;
    }

    public function createRules(array $input): array
    {
        return [
            'plan' => 'required',
            'region' => 'required',
        ];
    }

    public function credentialValidationRules(array $input): array
    {
        return [
            'token' => 'required',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'token' => $input['token'],
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
    public function connect(array $credentials): bool
    {
        try {
            $connect = Http::withToken($credentials['token'])->get($this->apiUrl.'/droplets');
        } catch (Exception) {
            throw new CouldNotConnectToProvider('DigitalOcean');
        }

        if (! $connect->ok()) {
            throw new CouldNotConnectToProvider('DigitalOcean');
        }

        return true;
    }

    /**
     * @return array<string, array{label: string, available: bool}>
     */
    public function plans(?string $region): array
    {
        try {
            /** @var array<string, mixed> $plans */
            $plans = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/sizes', ['per_page' => 200])
                ->json();

            /** @var array<int, array{slug: string, description: string, vcpus: int, memory: int, disk: int, price_monthly: int|float, regions: array<string>, available: bool}> $sizes */
            $sizes = $plans['sizes'] ?? [];

            return collect($sizes)
                ->map(function (array $size) use ($region): array {
                    $available = (bool) $size['available'] && in_array($region, $size['regions'], true);

                    $label = __('server_providers.plan', [
                        'name' => $size['description'],
                        'cpu' => $size['vcpus'],
                        'memory' => $size['memory'],
                        'disk' => $size['disk'],
                    ]);

                    if ($available) {
                        $label .= ' ('.number_format((float) $size['price_monthly'], 2).'/mo)';
                    }

                    return [
                        'slug' => $size['slug'],
                        'label' => $label,
                        'available' => $available,
                    ];
                })
                ->sortByDesc('available')
                ->mapWithKeys(fn (array $plan): array => [
                    $plan['slug'] => [
                        'label' => $plan['label'],
                        'available' => $plan['available'],
                    ],
                ])
                ->toArray();
        } catch (Exception) {
            return [];
        }
    }

    public function regions(): array
    {
        try {
            /** @var array{regions?: array<int, array{slug: string, name: string, available: bool}>} $regions */
            $regions = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/regions', ['per_page' => 200])
                ->json();

            $regionsList = $regions['regions'] ?? []; // Ensure it's always an array

            return collect($regionsList)
                ->where('available', true)
                ->mapWithKeys(fn (array $value): array => [$value['slug'] => $value['name']])
                ->toArray();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * @throws ServerProviderError
     */
    public function create(): void
    {
        $this->generateKeyPair();

        try {
            $createSshKey = Http::withToken($this->server->serverProvider->credentials['token'])
                ->post($this->apiUrl.'/account/keys', [
                    'public_key' => $this->server->sshKey()['public_key'],
                    'name' => str($this->server->name)->slug().'-'.$this->server->id,
                ]);
        } catch (Exception) {
            throw new ServerProviderError('DigitalOcean SSH Key');
        }

        if ($createSshKey->status() != 201) {
            throw new ServerProviderError('DigitalOcean SSH Key');
        }

        try {
            $create = Http::withToken($this->server->serverProvider->credentials['token'])
                ->post($this->apiUrl.'/droplets', [
                    'name' => str($this->server->name)->slug(),
                    'region' => $this->server->provider_data['region'],
                    'size' => $this->server->provider_data['plan'],
                    'image' => $this->getImageId($this->server->os, $this->server->provider_data['region']),
                    'backups' => false,
                    'ipv6' => false,
                    'monitoring' => false,
                    'ssh_keys' => [$createSshKey->json()['ssh_key']['id']],
                ]);
        } catch (Exception) {
            throw new ServerProviderError('DigitalOcean');
        }

        if ($create->status() != 202) {
            $msg = __('Failed to create server on DigitalOcean');
            Log::error('Failed to create server on DigitalOcean', $create->json());
            throw new ServerProviderError($msg);
        }
        $providerData = $this->server->provider_data;
        $providerData['droplet_id'] = $create->json()['droplet']['id'];
        $this->server->provider_data = $providerData;
        $this->server->save();
    }

    public function isRunning(): bool
    {
        try {
            $status = Http::withToken($this->server->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/droplets/'.$this->server->provider_data['droplet_id']);
        } catch (Exception) {
            return false;
        }

        if (! $status->ok()) {
            return false;
        }

        if (! $this->server->ip && count($status->json()['droplet']['networks']['v4']) > 0) {
            foreach ($status->json()['droplet']['networks']['v4'] as $v4) {
                if ($v4['type'] == 'public') {
                    $this->server->ip = $v4['ip_address'];
                } else {
                    $this->server->local_ip = $v4['ip_address'];
                }
            }
            $this->server->save();
        }

        if (! $this->server->ip) {
            return false;
        }

        return $status->json()['droplet']['status'] == 'active';
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        if (isset($this->server->provider_data['droplet_id'])) {
            $delete = Http::withToken($this->server->serverProvider->credentials['token'])
                ->delete($this->apiUrl.'/droplets/'.$this->server->provider_data['droplet_id']);

            if (! $delete->ok()) {
                Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getImageId(OperatingSystem $os, string $region): int
    {
        $version = $os->getVersion();

        try {
            $result = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/images', [
                    'per_page' => 200,
                    'type' => 'distribution',
                ])
                ->json();

            /** @var array<int, array{ id: int, name: string, distribution: string, status: string, regions: array<string> }> $images */
            $images = $result['images'] ?? []; // Ensure $images is an array

            $image = collect($images)
                ->filter(fn (array $image): bool => in_array($region, $image['regions']) && str_contains($image['name'], $version)
                )
                ->where('distribution', 'Ubuntu')
                ->where('status', 'available')
                ->first();

            return $image['id'] ?? 0; // Handle the case where first() returns null
        } catch (Exception) {
            throw new Exception('Could not find image ID');
        }
    }
}
