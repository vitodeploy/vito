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

    public function createRules(array $input): array
    {
        $rules = [
            'os' => 'required|in:'.implode(',', config('core.operating_systems')),
        ];
        // plans
        $plans = [];
        foreach (config('serverproviders.digitalocean.plans') as $plan) {
            $plans[] = $plan['value'];
        }
        $rules['plan'] = 'required|in:'.implode(',', $plans);
        // regions
        $regions = [];
        foreach (config('serverproviders.digitalocean.regions') as $region) {
            $regions[] = $region['value'];
        }
        $rules['region'] = 'required|in:'.implode(',', $regions);

        return $rules;
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
    public function connect(?array $credentials = null): bool
    {
        $connect = Http::withToken($credentials['token'])->get($this->apiUrl.'/account');
        if (! $connect->ok()) {
            throw new CouldNotConnectToProvider('DigitalOcean');
        }

        return true;
    }

    public function plans(): array
    {
        return config('serverproviders.digitalocean.plans');
    }

    public function regions(): array
    {
        return config('serverproviders.digitalocean.regions');
    }

    /**
     * @throws ServerProviderError
     */
    public function create(): void
    {
        $this->generateKeyPair();

        $createSshKey = Http::withToken($this->server->serverProvider->credentials['token'])
            ->post($this->apiUrl.'/account/keys', [
                'public_key' => $this->server->sshKey()['public_key'],
                'name' => str($this->server->name)->slug().'-'.$this->server->id,
            ]);
        if ($createSshKey->status() != 201) {
            throw new ServerProviderError('DigitalOcean SSH Key');
        }

        $create = Http::withToken($this->server->serverProvider->credentials['token'])
            ->post($this->apiUrl.'/droplets', [
                'name' => str($this->server->name)->slug(),
                'region' => $this->server->provider_data['region'],
                'size' => $this->server->provider_data['plan'],
                'image' => config('serverproviders.digitalocean.images')[$this->server->os],
                'backups' => false,
                'ipv6' => false,
                'monitoring' => false,
                'ssh_keys' => [$createSshKey->json()['ssh_key']['id']],
            ]);
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
        $status = Http::withToken($this->server->serverProvider->credentials['token'])
            ->get($this->apiUrl.'/droplets/'.$this->server->provider_data['droplet_id']);

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
}
