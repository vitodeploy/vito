<?php

namespace App\ServerProviders;

use App\Exceptions\CouldNotConnectToProvider;
use App\Exceptions\ServerProviderError;
use App\Facades\Notifier;
use App\Notifications\FailedToDeleteServerFromProvider;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Hetzner extends AbstractProvider
{
    protected string $apiUrl = 'https://api.hetzner.cloud/v1';

    public static function id(): string
    {
        return 'hetzner';
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
     * @throws ConnectionException
     */
    public function connect(array $credentials): bool
    {
        $connect = Http::withToken($credentials['token'])->get($this->apiUrl.'/servers');
        if (! $connect->ok()) {
            throw new CouldNotConnectToProvider('Hetzner');
        }

        return true;
    }

    /**
     * @return array<string, array{label: string, available: bool}>
     */
    public function plans(?string $region): array
    {
        try {
            /** @var array{server_types?: array<int, array{name: string, cores: int, memory: int, disk: int, prices: array<int, array{location: string, price_monthly: array{net: string}}>, locations: array<int, array{name: string, available: bool, deprecation: ?array}>}>} $plans */
            $plans = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/server_types', ['per_page' => 50])
                ->json();

            /** @var array<int, array{name: string, cores: int, memory: int, disk: int, prices: array<int, array{location: string, price_monthly: array{net: string}}>, locations: array<int, array{name: string, available: bool, deprecation: ?array}>}> $serverTypes */
            $serverTypes = $plans['server_types'] ?? [];

            return collect($serverTypes)
                ->map(function (array $type) use ($region): ?array {
                    /** @var array{name: string, available: bool, deprecation: ?array}|null $location */
                    $location = collect($type['locations'])->firstWhere('name', $region);

                    if (! $location) {
                        return null;
                    }

                    $available = $this->planIsAvailable($location);

                    $label = __('server_providers.plan', [
                        'name' => $type['name'],
                        'cpu' => $type['cores'],
                        'memory' => $type['memory'],
                        'disk' => $type['disk'],
                    ]);

                    if ($available) {
                        $price = $this->planMonthlyPrice($type, $region);

                        if ($price !== null) {
                            $label .= ' ('.number_format($price, 2).'/mo)';
                        }
                    }

                    return [
                        'name' => $type['name'],
                        'label' => $label,
                        'available' => $available,
                    ];
                })
                ->filter()
                ->sortByDesc('available')
                ->mapWithKeys(fn (array $plan): array => [
                    $plan['name'] => [
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
     * @param  array{available?: bool, deprecation?: ?array}  $location
     */
    private function planIsAvailable(array $location): bool
    {
        if (! ($location['available'] ?? false)) {
            return false;
        }

        $unavailableAfter = $location['deprecation']['unavailable_after'] ?? null;

        if ($unavailableAfter === null) {
            return true;
        }

        return Carbon::parse($unavailableAfter)->isFuture();
    }

    /**
     * @param  array{prices: array<int, array{location: string, price_monthly: array{net: string}}>}  $type
     */
    private function planMonthlyPrice(array $type, ?string $region): ?float
    {
        /** @var array{location: string, price_monthly: array{net: string}}|null $price */
        $price = collect($type['prices'])->firstWhere('location', $region);

        if ($price === null) {
            return null;
        }

        return (float) $price['price_monthly']['net'];
    }

    public function regions(): array
    {
        try {
            $regions = Http::withToken($this->serverProvider->credentials['token'])
                ->get($this->apiUrl.'/locations', ['per_page' => 50])
                ->json();

            /** @var array<int, array{name: string, city: string, country: string}> $locations */
            $locations = $regions['locations'];

            return collect($locations)
                ->mapWithKeys(fn (array $value): array => [$value['name'] => $value['city'].' - '.$value['country']])
                ->toArray();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * @throws ServerProviderError
     * @throws ConnectionException
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
                'image' => config('serverproviders.hetzner.images')[$this->server->os->value],
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

    /**
     * @throws ConnectionException
     */
    public function isRunning(): bool
    {
        $status = Http::withToken($this->server->serverProvider->credentials['token'])
            ->get($this->apiUrl.'/servers/'.$this->server->provider_data['hetzner_id']);

        if (! $status->ok()) {
            return false;
        }

        return $status->json()['server']['status'] == 'running';
    }

    /**
     * @throws ConnectionException
     */
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
