<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\Models\StorageProvider;
use App\SSH\Storage\S3 as S3Storage;
use App\SSH\Storage\Storage;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

class S3 extends AbstractStorageProvider
{
    protected StorageProvider $storageProvider;

    protected ?S3Client $client = null;

    protected array $clientConfig = [];

    public function getApiUrl(): string
    {
        if (isset($this->storageProvider->credentials['api_url']) && $this->storageProvider->credentials['api_url']) {
            return $this->storageProvider->credentials['api_url'];
        }

        $region = $this->storageProvider->credentials['region'];

        return "https://s3.{$region}.amazonaws.com";
    }

    public function getClient(): S3Client
    {
        return new S3Client($this->clientConfig);
    }

    /**
     * Build the configuration array for the S3 client.
     * This method can be overridden by child classes to modify the configuration.
     */
    public function buildClientConfig(): array
    {
        $this->clientConfig = [
            'credentials' => [
                'key' => $this->storageProvider->credentials['key'],
                'secret' => $this->storageProvider->credentials['secret'],
            ],
            'region' => $this->storageProvider->credentials['region'],
            'version' => 'latest',
            'endpoint' => $this->getApiUrl(),
        ];

        return $this->clientConfig;
    }

    public function validationRules(): array
    {
        return [
            'api_url' => 'nullable',
            'key' => 'required',
            'secret' => 'required',
            'region' => 'required',
            'bucket' => 'required',
            'path' => 'nullable',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'api_url' => $input['api_url'] ?? '',
            'key' => $input['key'],
            'secret' => $input['secret'],
            'region' => $input['region'],
            'bucket' => $input['bucket'],
            'path' => $input['path'] ?? '',
        ];
    }

    public function connect(): bool
    {
        try {
            $this->buildClientConfig();
            $this->getClient()->listBuckets();

            return true;
        } catch (S3Exception $e) {
            Log::error('Failed to connect to the provider', ['exception' => $e]);

            return false;
        }
    }

    public function ssh(Server $server): Storage
    {
        return new S3Storage($server, $this->storageProvider);
    }
}
