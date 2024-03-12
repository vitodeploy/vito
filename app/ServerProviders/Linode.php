<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Linode extends AbstractProvider
{
    protected string $apiUrl = 'https://api.linode.com/v4';

    public function createRules($input): array
    {
        $rules = [
            'os' => 'required|in:'.implode(',', config('core.operating_systems')),
        ];
        // plans
        $plans = [];
        foreach (config('serverproviders.linode.plans') as $plan) {
            $plans[] = $plan['value'];
        }
        $rules['plan'] = 'required|in:'.implode(',', $plans);
        // regions
        $regions = [];
        foreach (config('serverproviders.linode.regions') as $region) {
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
            throw new CouldNotConnectToProvider('Linode');
        }

        return true;
    }

    public function plans(): array
    {
        return config('serverproviders.linode.plans');
    }

    public function regions(): array
    {
        return config('serverproviders.linode.regions');
    }

    /**
     * @throws ServerProviderError
     */
    public function create(): void
    {
        $this->generateKeyPair();

        $create = Http::withToken($this->server->serverProvider->credentials['token'])
            ->post($this->apiUrl.'/linode/instances', [
                'backups_enabled' => false,
                'image' => config('serverproviders.linode.images')[$this->server->os],
                'root_pass' => $this->server->authentication['root_pass'],
                'authorized_keys' => [
                    $this->server->sshKey()['public_key'],
                ],
                'booted' => true,
                'label' => str($this->server->name)->slug(),
                'type' => $this->server->provider_data['plan'],
                'region' => $this->server->provider_data['region'],
            ]);
        if (! $create->ok()) {
            $msg = __('Failed to create server on Linode');
            $errors = $create->json('errors');
            if (count($errors) > 0) {
                $msg = $errors[0]['reason'];
            }
            Log::error('Linode error', $errors);
            throw new ServerProviderError($msg);
        }
        $this->server->ip = $create->json()['ipv4'][0];
        $providerData = $this->server->provider_data;
        $providerData['linode_id'] = $create->json()['id'];
        $this->server->provider_data = $providerData;
        $this->server->save();
    }

    public function isRunning(): bool
    {
        $status = Http::withToken($this->server->serverProvider->credentials['token'])
            ->get($this->apiUrl.'/linode/instances/'.$this->server->provider_data['linode_id']);

        if (! $status->ok()) {
            return false;
        }

        return $status->json()['status'] == 'running';
    }

    public function delete(): void
    {
        if (isset($this->server->provider_data['linode_id'])) {
            $delete = Http::withToken($this->server->serverProvider->credentials['token'])
                ->delete($this->apiUrl.'/linode/instances/'.$this->server->provider_data['linode_id']);

            if (! $delete->ok()) {
                Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));
            }
        }
    }
}
