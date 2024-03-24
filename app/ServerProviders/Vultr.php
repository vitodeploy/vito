<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Vultr extends AbstractProvider
{
    protected string $apiUrl = 'https://api.vultr.com/v2';

    public function createRules($input): array
    {
        $rules = [
            'os' => 'required|in:'.implode(',', config('core.operating_systems')),
        ];
        // plans
        $plans = [];
        foreach (config('serverproviders.vultr.plans') as $plan) {
            $plans[] = $plan['value'];
        }
        $rules['plan'] = 'required|in:'.implode(',', $plans);
        // regions
        $regions = [];
        foreach (config('serverproviders.vultr.regions') as $region) {
            $regions[] = $region['value'];
        }
        $rules['region'] = 'required|in:'.implode(',', $regions);

        return $rules;
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
        $connect = Http::withToken($credentials['token'])->get($this->apiUrl.'/account');
        if (! $connect->ok()) {
            throw new CouldNotConnectToProvider('Vultr');
        }

        return true;
    }

    public function plans(): array
    {
        return config('serverproviders.vultr.plans');
    }

    public function regions(): array
    {
        return config('serverproviders.vultr.regions');
    }

    /**
     * @throws ServerProviderError
     */
    public function create(): void
    {
        // generate key pair
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storageDisk */
        $storageDisk = Storage::disk(config('core.key_pairs_disk'));
        generate_key_pair($storageDisk->path((string) $this->server->id));

        $createSshKey = Http::withToken($this->server->serverProvider->credentials['token'])
            ->post($this->apiUrl.'/ssh-keys', [
                'ssh_key' => $this->server->sshKey()['public_key'],
                'name' => $this->server->name.'_'.$this->server->id,
            ]);
        if ($createSshKey->status() != 201) {
            throw new ServerProviderError('Error creating SSH Key on Vultr');
        }

        $create = Http::withToken($this->server->serverProvider->credentials['token'])
            ->post($this->apiUrl.'/instances', [
                'label' => $this->server->name,
                'region' => $this->server->provider_data['region'],
                'plan' => $this->server->provider_data['plan'],
                'os_id' => config('serverproviders.vultr.images')[$this->server->os],
                'enable_ipv6' => false,
                'sshkey_id' => [$createSshKey->json()['ssh_key']['id']],
            ]);
        if ($create->status() != 202) {
            $msg = __('Failed to create server on Vultr');
            Log::error('Failed to create server on Vultr', $create->json());
            throw new ServerProviderError($msg);
        }
        $providerData = $this->server->provider_data;
        $providerData['instance_id'] = $create->json()['instance']['id'];
        $this->server->provider_data = $providerData;
        $this->server->save();
    }

    public function isRunning(): bool
    {
        $status = Http::withToken($this->server->serverProvider->credentials['token'])
            ->get($this->apiUrl.'/instances/'.$this->server->provider_data['instance_id']);

        if (! $status->ok()) {
            return false;
        }

        if (! $this->server->ip) {
            $this->server->ip = $status->json()['instance']['main_ip'];
            $this->server->save();
        }

        return $status->json()['instance']['status'] == 'active';
    }

    /**
     * @throws Exception
     */
    public function delete(): void
    {
        if (isset($this->server->provider_data['instance_id'])) {
            $delete = Http::withToken($this->server->serverProvider->credentials['token'])
                ->delete($this->apiUrl.'/instances/'.$this->server->provider_data['instance_id']);

            if (! $delete->ok()) {
                Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));
            }
        }
    }
}
