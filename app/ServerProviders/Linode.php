<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Linode extends AbstractProvider
{
    protected string $apiUrl = 'https://api.linode.com/v4';

    public static function id(): string
    {
        return 'linode';
    }

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
    public function connect(array $credentials): bool
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

    /**
     * @return array<string, array{label: string, available: bool}>
     */
    public function plans(?string $region): array
    {
        try {
            /** @var array<string, mixed> $response */
            $response = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/linode/types')
                ->json();

            /** @var array<int, array{id: string, label: string, vcpus: int, memory: int, disk: int, class?: string, price?: array{monthly?: float}, region_prices?: array<int, array{id: string, monthly: float}>}> $types */
            $types = $response['data'] ?? [];

            $capabilities = $this->regionCapabilities($region);

            return collect($types)
                ->map(function (array $type) use ($region, $capabilities): array {
                    $available = $this->planIsAvailable($type['class'] ?? '', $capabilities);

                    $label = __('server_providers.plan', [
                        'name' => $type['label'],
                        'cpu' => $type['vcpus'],
                        'memory' => $type['memory'],
                        'disk' => intdiv((int) $type['disk'], 1024),
                    ]);

                    if ($available) {
                        $price = $this->planMonthlyPrice($type, $region);

                        if ($price !== null) {
                            $label .= ' ('.number_format($price, 2).'/mo)';
                        }
                    }

                    return [
                        'id' => $type['id'],
                        'label' => $label,
                        'available' => $available,
                    ];
                })
                ->sortByDesc('available')
                ->mapWithKeys(fn (array $plan): array => [
                    $plan['id'] => [
                        'label' => $plan['label'],
                        'available' => $plan['available'],
                    ],
                ])
                ->toArray();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * @param  array<int, string>  $capabilities
     */
    private function planIsAvailable(string $class, array $capabilities): bool
    {
        if ($capabilities === []) {
            return true;
        }

        return match ($class) {
            'gpu' => in_array('GPU Linodes', $capabilities, true),
            'premium' => in_array('Premium Plans', $capabilities, true),
            default => in_array('Linodes', $capabilities, true),
        };
    }

    /**
     * @return array<int, string>
     */
    private function regionCapabilities(?string $region): array
    {
        /** @var array{data?: array<int, array{id: string, capabilities: array<int, string>}>} $response */
        $response = Http::withToken($this->serverProvider->credentials['token'])
            ->get($this->apiUrl.'/regions')
            ->json();

        /** @var array{id: string, capabilities: array<int, string>}|null $match */
        $match = collect($response['data'] ?? [])->firstWhere('id', $region);

        return $match['capabilities'] ?? [];
    }

    /**
     * @param  array{price?: array{monthly?: float}, region_prices?: array<int, array{id: string, monthly: float}>}  $type
     */
    private function planMonthlyPrice(array $type, ?string $region): ?float
    {
        /** @var array{id: string, monthly: float}|null $regionPrice */
        $regionPrice = collect($type['region_prices'] ?? [])->firstWhere('id', $region);

        if ($regionPrice !== null) {
            return (float) $regionPrice['monthly'];
        }

        $monthly = $type['price']['monthly'] ?? null;

        return $monthly !== null ? (float) $monthly : null;
    }

    public function regions(): array
    {
        try {
            /** @var array<string, mixed> $regions */
            $regions = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/regions')
                ->json();

            /** @var array<int, array<string, mixed>> $regionsData */
            $regionsData = $regions['data'];

            return collect($regionsData)
                ->mapWithKeys(fn (array $value) => [$value['id'] => $value['label']])
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
                    'image' => config('serverproviders.linode.images')[$this->server->os->value],
                    'root_pass' => $this->server->authentication['root_pass'],
                    'authorized_keys' => [
                        $this->server->sshKey()['public_key'],
                    ],
                    'booted' => true,
                    'label' => str($this->server->name)->slug(),
                    'type' => $this->server->provider_data['plan'],
                    'region' => $this->server->provider_data['region'],
                ]);
        } catch (Exception) {
            throw new ServerProviderError('Failed to create server on Linode');
        }

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
}
