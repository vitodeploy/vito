<?php

namespace App\ServerProviders;

use App\Exceptions\ServerProviderError;
use Aws\Exception\AwsException;
use Aws\Lightsail\LightsailClient;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use phpseclib3\Crypt\RSA;
use SensitiveParameter;

class Lightsail extends AbstractProvider
{
    public static function id(): string
    {
        return 'lightsail';
    }

    public function createRules(array $input): array
    {
        return [
            'plan' => ['required', 'string'],
            'region' => ['required', 'string', 'regex:/^[a-z]{2}(?:-[a-z]+)+-\d+$/'],
        ];
    }

    public function credentialValidationRules(array $input): array
    {
        return [
            'key' => ['required', 'string'],
            'secret' => ['required', 'string'],
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'key' => $input['key'],
            'secret' => $input['secret'],
        ];
    }

    public function data(array $input): array
    {
        return [
            'plan' => $input['plan'],
            'region' => $input['region'],
        ];
    }

    public function connect(#[SensitiveParameter] array $credentials): bool
    {
        $this->request('GetRegions', credentials: $this->credentialData($credentials));

        return true;
    }

    public function regions(): array
    {
        return collect($this->request('GetRegions')['regions'] ?? [])
            ->mapWithKeys(fn (array $region): array => [
                $region['name'] => $region['displayName'].' ('.$region['name'].')',
            ])
            ->all();
    }

    public function plans(?string $region): array
    {
        if (! $region) {
            return [];
        }

        return collect($this->paginate('GetBundles', 'bundles', $region))
            ->filter(fn (array $bundle): bool => ($bundle['isActive'] ?? false)
                && in_array('LINUX_UNIX', $bundle['supportedPlatforms'] ?? [], true)
                && ($bundle['publicIpv4AddressCount'] ?? 0) > 0)
            ->mapWithKeys(fn (array $bundle): array => [
                $bundle['bundleId'] => __('server_providers.plan', [
                    'name' => $bundle['name'],
                    'cpu' => $bundle['cpuCount'],
                    'memory' => $bundle['ramSizeInGb'] * 1024,
                    'disk' => $bundle['diskSizeInGb'],
                ]).' ('.number_format($bundle['price'], 2).'/mo)',
            ])
            ->all();
    }

    public function create(): void
    {
        $region = $this->server->provider_data['region'];
        $regions = $this->request('GetRegions', ['includeAvailabilityZones' => true]);
        $location = collect($regions['regions'] ?? [])->firstWhere('name', $region);
        $zone = $location['availabilityZones'][0]['zoneName'] ?? null;

        if (! $zone) {
            throw new ServerProviderError('The selected AWS Lightsail region is unavailable.');
        }

        if (! isset($this->plans($region)[$this->server->provider_data['plan']])) {
            throw new ServerProviderError('The selected AWS Lightsail plan is unavailable.');
        }

        $blueprint = collect($this->paginate('GetBlueprints', 'blueprints', $region))
            ->first(fn (array $blueprint): bool => ($blueprint['isActive'] ?? false)
                && ($blueprint['group'] ?? '') === 'ubuntu'
                && ($blueprint['type'] ?? '') === 'os'
                && str_starts_with($blueprint['version'] ?? '', $this->server->os->getVersion()));

        if (! $blueprint) {
            throw new ServerProviderError('The selected Ubuntu version is unavailable on AWS Lightsail.');
        }

        $name = 'vito-'.$this->server->id.'-'.Str::lower(Str::random(12));
        $this->generateKeyPair();
        $this->server->jsonUpdate('provider_data', 'ssh_key_name', $name);
        $this->request('ImportKeyPair', [
            'keyPairName' => $name,
            'publicKeyBase64' => base64_encode($this->server->sshKey()['public_key']),
        ]);

        $this->server->jsonUpdate('provider_data', 'instance_name', $name);
        $this->request('CreateInstances', [
            'instanceNames' => [$name],
            'availabilityZone' => $zone,
            'blueprintId' => $blueprint['blueprintId'],
            'bundleId' => $this->server->provider_data['plan'],
            'keyPairName' => $name,
            'ipAddressType' => 'ipv4',
        ]);
    }

    public function generateKeyPair(): void
    {
        $key = RSA::createKey(2048);
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(config('core.key_pairs_disk'));
        $disk->put((string) $this->server->id, $key->toString('PKCS8'));
        chmod($disk->path((string) $this->server->id), 0400);
        $disk->put($this->server->id.'.pub', $key->getPublicKey()->toString('OpenSSH'));
    }

    public function isRunning(): bool
    {
        if (! isset($this->server->provider_data['instance_name'])) {
            return false;
        }

        $result = $this->request('GetInstance', [
            'instanceName' => $this->server->provider_data['instance_name'],
        ]);
        $instance = $result['instance'] ?? [];

        if (($instance['state']['name'] ?? '') !== 'running' || empty($instance['publicIpAddress'])) {
            return false;
        }

        if (! ($this->server->provider_data['firewall_configured'] ?? false)) {
            $this->request('PutInstancePublicPorts', [
                'instanceName' => $this->server->provider_data['instance_name'],
                'portInfos' => [[
                    'fromPort' => 0,
                    'toPort' => 65535,
                    'protocol' => 'all',
                    'cidrs' => ['0.0.0.0/0'],
                ]],
            ]);
            $this->server->jsonUpdate('provider_data', 'firewall_configured', true, false);
        }

        $this->server->ip = $instance['publicIpAddress'];
        $this->server->local_ip = $instance['privateIpAddress'] ?? null;
        $this->server->save();

        return true;
    }

    public function delete(): void
    {
        if (isset($this->server->provider_data['instance_name'])) {
            $this->request('DeleteInstance', [
                'instanceName' => $this->server->provider_data['instance_name'],
            ]);
        }

        if (isset($this->server->provider_data['ssh_key_name'])) {
            $this->request('DeleteKeyPair', [
                'keyPairName' => $this->server->provider_data['ssh_key_name'],
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paginate(string $operation, string $key, string $region): array
    {
        $items = [];
        $parameters = ['includeInactive' => false];

        do {
            $result = $this->request($operation, $parameters, $region);
            $items = array_merge($items, $result[$key] ?? []);
            $parameters['pageToken'] = $result['nextPageToken'] ?? null;
        } while ($parameters['pageToken']);

        return $items;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array{key: string, secret: string}|null  $credentials
     * @return array<string, mixed>
     */
    private function request(string $operation, array $parameters = [], ?string $region = null, #[SensitiveParameter] ?array $credentials = null): array
    {
        $region ??= $this->server->provider_data['region'] ?? 'us-east-1';

        if (! preg_match('/^[a-z]{2}(?:-[a-z]+)+-\d+$/', $region)) {
            throw ValidationException::withMessages(['region' => 'Invalid AWS Lightsail region.']);
        }

        try {
            $client = app(LightsailClient::class, ['args' => [
                'version' => '2016-11-28',
                'region' => $region,
                'credentials' => $credentials ?? $this->serverProvider->getCredentials(),
            ]]);
            $result = $client->execute($client->getCommand($operation, $parameters))->toArray();
        } catch (AwsException $exception) {
            if ($exception->getAwsErrorCode() === 'NotFoundException'
                && in_array($operation, ['GetInstance', 'DeleteInstance', 'DeleteKeyPair'], true)) {
                return [];
            }

            throw new ServerProviderError('AWS Lightsail could not complete '.$operation.'. Check the provider permissions and try again.');
        }

        foreach ($result['operations'] ?? (isset($result['operation']) ? [$result['operation']] : []) as $operationResult) {
            if (($operationResult['status'] ?? '') === 'Failed') {
                throw new ServerProviderError('AWS Lightsail could not complete '.$operation.'.');
            }
        }

        return $result;
    }
}
