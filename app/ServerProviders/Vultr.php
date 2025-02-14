<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Vultr extends AbstractProvider
{
    protected string $apiUrl = 'https://api.vultr.com/v2';

    public function createRules(array $input): array
    {
        return [
            'plan' => 'required',
            'region' => 'required',
        ];
    }

    public function credentialValidationRules($input): array
    {
        return [
            'token' => 'required',
        ];
    }

    public function credentialData($input): array
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
    public function connect(?array $credentials = null): bool
    {
        try {
            $connect = Http::withToken($credentials['token'])->get($this->apiUrl.'/account');
        } catch (Exception) {
            throw new CouldNotConnectToProvider('Vultr');
        }

        if (! $connect->ok()) {
            throw new CouldNotConnectToProvider('Vultr');
        }

        return true;
    }

    public function plans(?string $region): array
    {
        try {
            $plans = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/plans', ['per_page' => 500])
                ->json();

            return collect($plans['plans'])->filter(function ($plan) use ($region) {
                return in_array($region, $plan['locations']);
            })
                ->mapWithKeys(function ($value) {
                    return [
                        $value['id'] => __('server_providers.plan', [
                            'name' => $value['type'],
                            'cpu' => $value['vcpu_count'],
                            'memory' => $value['ram'],
                            'disk' => $value['disk'],
                        ]),
                    ];
                })
                ->toArray();
        } catch (Exception) {
            return [];
        }
    }

    public function regions(): array
    {
        try {
            $regions = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/regions', ['per_page' => 500])
                ->json();

            return collect($regions['regions'])
                ->mapWithKeys(fn ($value) => [$value['id'] => $value['country'].' - '.$value['city']])
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
        // generate key pair
        /** @var FilesystemAdapter $storageDisk */
        $storageDisk = Storage::disk(config('core.key_pairs_disk'));
        generate_key_pair($storageDisk->path((string) $this->server->id));

        try {
            $createSshKey = Http::withToken($this->server->serverProvider->credentials['token'])
                ->post($this->apiUrl.'/ssh-keys', [
                    'ssh_key' => $this->server->sshKey()['public_key'],
                    'name' => $this->server->name.'_'.$this->server->id,
                ]);
        } catch (Exception) {
            throw new ServerProviderError('Error creating SSH Key on Vultr');
        }

        if ($createSshKey->status() != 201) {
            throw new ServerProviderError('Error creating SSH Key on Vultr');
        }

        try {
            $create = Http::withToken($this->server->serverProvider->credentials['token'])
                ->post($this->apiUrl.'/instances', [
                    'label' => $this->server->name,
                    'region' => $this->server->provider_data['region'],
                    'plan' => $this->server->provider_data['plan'],
                    'os_id' => $this->getImageId($this->server->os),
                    'enable_ipv6' => false,
                    'sshkey_id' => [$createSshKey->json()['ssh_key']['id']],
                ]);
        } catch (Exception) {
            throw new ServerProviderError('Failed to create server on Vultr');
        }

        if ($create->status() != 202) {
            Log::error('Failed to create server on Vultr', $create->json());
            throw new ServerProviderError('Failed: '.$create->body());
        }
        $providerData = $this->server->provider_data;
        $providerData['instance_id'] = $create->json()['instance']['id'];
        $this->server->provider_data = $providerData;
        $this->server->save();
    }

    public function isRunning(): bool
    {
        try {
            $status = Http::withToken($this->server->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/instances/'.$this->server->provider_data['instance_id']);
        } catch (Exception) {
            return false;
        }

        if (! $status->ok()) {
            return false;
        }

        if (! $this->server->ip) {
            $this->server->ip = $status->json()['instance']['main_ip'];
            $this->server->save();
        }

        if (! $this->server->ip) {
            return false;
        }

        return $status->json()['instance']['status'] == 'active';
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        if (isset($this->server->provider_data['instance_id'])) {
            try {
                $delete = Http::withToken($this->server->serverProvider->credentials['token'])
                    ->delete($this->apiUrl.'/instances/'.$this->server->provider_data['instance_id']);
            } catch (Exception) {
                Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));

                return;
            }

            if (! $delete->ok()) {
                Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));
            }
        }
    }

    /**
     * @throws Exception
     */
    private function getImageId(string $os): int
    {
        $version = config('core.operating_system_versions.'.$os);

        try {
            $result = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/os', ['per_page' => 500])
                ->json();

            $image = collect($result['os'])
                ->filter(function ($os) use ($version) {
                    return str_contains($os['name'], $version);
                })
                ->where('family', 'ubuntu')
                ->where('arch', 'x64')
                ->first();

            return $image['id'];
        } catch (Exception) {
            throw new Exception('Could not find image ID');
        }
    }
}
