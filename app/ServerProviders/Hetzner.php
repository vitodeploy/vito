<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Hetzner extends AbstractProvider
{
    protected string $apiUrl = 'https://api.hetzner.cloud/v1';

    public function createRules(array $input): array
    {
        return [
            'os' => 'required|in:'.implode(',', config('core.operating_systems')),
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
    public function connect(?array $credentials = null): bool
    {
        $connect = Http::withToken($credentials['token'])->get($this->apiUrl.'/servers');
        if (! $connect->ok()) {
            throw new CouldNotConnectToProvider('Hetzner');
        }

        return true;
    }

    public function plans(): array
    {
        return config('serverproviders.hetzner.plans');
    }

    public function regions(): array
    {
        return config('serverproviders.hetzner.regions');
    }

    /**
     * @throws ServerProviderError
     */
    public function create(): void
    {
        $this->generateKeyPair();

        $sshKey = Http::withToken($this->server->serverProvider->credentials['token'])
            ->post($this->apiUrl.'/ssh_keys', [
                'name' => 'server-'.$this->server->id.'-key',
                'public_key' => $this->server->sshKey()['public_key'],
            ]);

        if ($sshKey->status() != 201) {
            $this->providerError($sshKey);
        }

        $this->server->jsonUpdate('provider_data', 'ssh_key_id', $sshKey->json()['ssh_key']['id']);

        $create = Http::withToken($this->server->serverProvider->credentials['token'])
            ->post($this->apiUrl.'/servers', [
                'automount' => false,
                'image' => config('serverproviders.hetzner.images')[$this->server->os],
                // 'root_password' => $this->server->authentication['root_pass'],
                'ssh_keys' => [
                    $sshKey->json()['ssh_key']['id'],
                ],
                'name' => str($this->server->name)->slug(),
                'location' => $this->server->provider_data['region'],
                'server_type' => $this->server->provider_data['plan'],
            ]);
        if ($create->status() != 201) {
            $this->providerError($create);
        }
        $this->server->jsonUpdate('provider_data', 'hetzner_id', $create->json()['server']['id'], false);
        $this->server->ip = $create->json()['server']['public_net']['ipv4']['ip'];
        $this->server->save();
    }

    public function isRunning(): bool
    {
        $status = Http::withToken($this->server->serverProvider->credentials['token'])
            ->get($this->apiUrl.'/servers/'.$this->server->provider_data['hetzner_id']);

        if (! $status->ok()) {
            return false;
        }

        return $status->json()['server']['status'] == 'running';
    }

    public function delete(): void
    {
        if (isset($this->server->provider_data['hetzner_id'])) {
            $delete = Http::withToken($this->server->serverProvider->credentials['token'])
                ->delete($this->apiUrl.'/servers/'.$this->server->provider_data['hetzner_id']);

            if (! $delete->ok()) {
                Notifier::send($this->server, new FailedToDeleteServerFromProvider($this->server));
            }
        }

        // delete key
        if (isset($this->server->provider_data['ssh_key_id'])) {
            Http::withToken($this->server->serverProvider->credentials['token'])
                ->delete($this->apiUrl.'/ssh_keys/'.$this->server->provider_data['ssh_key_id']);
        }
    }

    /**
     * @throws ServerProviderError
     */
    private function providerError(Response $response): void
    {
        throw new ServerProviderError($response->json('error')['message']);
    }
}
