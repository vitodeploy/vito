<?php

namespace App\StorageProviders;

use App\DTOs\DynamicField;
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

    /**
     * @var array<string, mixed>
     */
    protected array $clientConfig = [];

    public static function id(): string
    {
        return 's3';
    }

    /**
     * @param  array<string, mixed>|null  $credentials
     */
    public function getApiUrl(?array $credentials = null): string
    {
        $credentials ??= $this->storageProvider->credentials;

        if (isset($credentials['api_url']) && $credentials['api_url']) {
            return $credentials['api_url'];
        }

        $region = $credentials['region'];

        return "https://s3.{$region}.amazonaws.com";
    }

    public function getClient(): S3Client
    {
        return new S3Client($this->clientConfig);
    }

    /**
     * Build the configuration array for the S3 client.
     * This method can be overridden by child classes to modify the configuration.
     *
     * @param  array<string, mixed>|null  $credentials
     * @return array<string, mixed>
     */
    public function buildClientConfig(?array $credentials = null): array
    {
        $credentials ??= $this->storageProvider->credentials;

        $this->clientConfig = [
            'credentials' => [
                'key' => $credentials['key'],
                'secret' => $credentials['secret'],
            ],
            'region' => $credentials['region'],
            'version' => 'latest',
            'endpoint' => $this->getApiUrl($credentials),
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

    public static function editFields(): array
    {
        return [
            DynamicField::make('api_url')
                ->text()
                ->label('API URL'),
            DynamicField::make('key')
                ->text()
                ->label('Access Key'),
            DynamicField::make('secret')
                ->passwordWithToggle()
                ->label('Secret Key')
                ->description('Leave empty to keep the current secret key'),
            DynamicField::make('region')
                ->text()
                ->label('Region'),
            DynamicField::make('bucket')
                ->text()
                ->label('Bucket Name'),
            DynamicField::make('path')
                ->text()
                ->label('Path'),
        ];
    }

    protected function editableFields(): array
    {
        return ['api_url', 'key', 'region', 'bucket', 'path'];
    }

    protected function secretFields(): array
    {
        return ['secret'];
    }

    public function connect(array $credentials): bool
    {
        try {
            $this->buildClientConfig($credentials);
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
