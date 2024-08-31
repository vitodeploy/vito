<?php

namespace App\StorageProviders;

use App\Models\StorageProvider;
use Aws\S3\S3Client;

abstract class S3AbstractStorageProvider extends AbstractStorageProvider implements S3ClientInterface, S3StorageInterface
{
    protected ?string $apiUrl = null;

    protected ?string $bucketRegion = null;

    protected ?S3Client $client = null;

    protected StorageProvider $storageProvider;

    protected array $clientConfig = [];

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function setApiUrl(?string $region = null): void
    {
        $this->bucketRegion = $region ?? $this->bucketRegion;
        $this->apiUrl = "https://s3.{$this->bucketRegion}.amazonaws.com";
    }

    public function getBucketRegion(): string
    {
        return $this->bucketRegion;
    }

    public function setBucketRegion(string $region): void
    {
        $this->bucketRegion = $region;
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
            'region' => $this->getBucketRegion(),
            'version' => 'latest',
            'endpoint' => $this->getApiUrl(),
        ];

        return $this->clientConfig;
    }

    /**
     * Set or update a configuration parameter for the S3 client.
     */
    public function setConfigParam(array $param): void
    {
        foreach ($param as $key => $value) {
            $this->clientConfig[$key] = $value;
        }
    }
}
