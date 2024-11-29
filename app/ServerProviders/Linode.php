<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Linode extends AbstractProvider
{
    protected string $apiUrl = 'https://api.linode.com/v4';

    public function createRules(array $input): array
    {
        return [
            'plan' => ['required'],
            'region' => ['required'],
            'os' => ['required'],
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
            'os' => $input['os'],
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
            throw new CouldNotConnectToProvider('Linode');
        }

        if (! $connect->ok()) {
            throw new CouldNotConnectToProvider('Linode');
        }

        return true;
    }

    public function plans(?string $region, ?string $zone = null): array
    {
        try {
            $plans = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/linode/types')
                ->json();

            return collect($plans['data'])
                ->mapWithKeys(function ($value) {
                    return [
                        $value['id'] => __('server_providers.plan', [
                            'name' => $value['label'],
                            'cpu' => $value['vcpus'],
                            'memory' => round($value['memory'] / 1024, 2),
                            'disk' => round($value['disk'] / 1024, 2),
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
                ->get($this->apiUrl.'/regions')
                ->json();

            return collect($regions['data'])
                ->sortByDesc('country')
                ->mapWithKeys(fn ($value) => [$value['id'] => $value['label']])
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
            $create = Http::withToken($this->server->serverProvider->credentials['token'])
                ->post($this->apiUrl.'/linode/instances', [
                    'backups_enabled' => false,
                    'image' => $this->getImageId($this->server->os),
                    'root_pass' => $this->server->authentication['root_pass'],
                    'authorized_keys' => [
                        $this->server->sshKey()['public_key'],
                    ],
                    'booted' => true,
                    'label' => str($this->server->name)->slug(),
                    'type' => $this->server->provider_data['plan'],
                    'region' => $this->server->provider_data['region'],
                ]);
        } catch (Exception $e) {
            throw new ServerProviderError('Failed to create server on Linode: '.$e->getMessage());
        }

        if (! $create->ok()) {
            $message = __('Failed to create server on Linode');
            $errors = $create->json('errors');
            if (count($errors) > 0) {
                $message = $message.': '.$errors[0]['reason'];
            }
            Log::error('Failed to create server on Linode', $errors[0]);
            throw new ServerProviderError($message);
        }

        $this->server->jsonUpdate('provider_data', 'linode_id', $create->json()['id'], false);
        $this->server->ip = $create->json()['ipv4'][0];
        $this->server->save();
    }

    public function isRunning(): bool
    {
        try {
            $status = Http::withToken($this->server->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/linode/instances/'.$this->server->provider_data['linode_id']);
        } catch (Exception) {
            return false;
        }

        if (! $status->ok()) {
            return false;
        }

        return $status->json()['status'] == 'running';
    }

    public function delete(): void
    {
        if (isset($this->server->provider_data['linode_id'])) {
            try {
                $delete = Http::withToken($this->server->serverProvider->credentials['token'])
                    ->delete($this->apiUrl.'/linode/instances/'.$this->server->provider_data['linode_id']);
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
    private function getImageId(string $os): string
    {
        $version = config('core.operating_system_versions.'.$os);

        if (Cache::get('linode-image-'.$os.'-'.$version)) {
            return Cache::get('linode-image-'.$os.'-'.$version);
        }

        try {
            $result = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/images')
                ->json();

            $image = collect($result['data'])
                ->filter(function ($data) use ($version) {
                    return str_contains($data['id'], $version) && str_contains($data['label'], $version);
                })
                ->where('vendor', 'Ubuntu')
                ->where('status', 'available')
                ->sortByDesc('updated')
                ->first();

            if (! empty($image)) {
                Cache::put('linode-image-'.$os.'-'.$version, $image['id'], 600);
                return $image['id'];
            }

            throw new ServerProviderError('Could not find image ID for '.$os);
        } catch (Exception $e) {
            throw new ServerProviderError($e->getMessage());
        }
    }
}
