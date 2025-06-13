<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DigitalOcean extends AbstractProvider
{
    protected string $apiUrl = 'https://api.digitalocean.com/v2';

    public static function id(): string
    {
        return 'digitalocean';
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
            $connect = Http::withToken($credentials['token'])->get($this->apiUrl.'/account');
        } catch (Exception) {
            throw new CouldNotConnectToProvider('DigitalOcean');
        }

        if (! $connect->ok()) {
            throw new CouldNotConnectToProvider('DigitalOcean');
        }

        return true;
    }

    public function plans(?string $region): array
    {
        try {
            /** @var array<string, mixed> $plans */
            $plans = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/sizes', ['per_page' => 200])
                ->json();

            /** @var array<int, array{slug: string, description: string, vcpus: int, memory: int, disk: int, regions: array<string>}> $sizes */
            $sizes = $plans['sizes'] ?? [];

            return collect($sizes)
                ->filter(fn (array $size): bool => in_array($region, $size['regions']))
                ->mapWithKeys(fn (array $value): array => [
                    $value['slug'] => __('server_providers.plan', [
                        'name' => $value['description'],
                        'cpu' => $value['vcpus'],
                        'memory' => $value['memory'],
                        'disk' => $value['disk'],
                    ]),
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
    private function getImageId(string $os, string $region): int
    {
        $version = config('core.operating_system_versions.'.$os);

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
                ->filter(fn (array $image): bool => in_array($region, $image['regions']) && str_contains($image['name'], (string) $version)
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
