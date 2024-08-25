<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSH\Storage\Storage;
use App\SSH\Storage\Wasabi as WasabiStorage;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Log;

class Wasabi extends AbstractStorageProvider
{
    private const API_URL = 'https://s3.wasabisys.com';
    private const DEFAULT_REGION = 'us-east-1';

    private ?S3Client $client = null;

    public function validationRules(): array
    {
        return [
            'key' => 'required|string',
            'secret' => 'required|string',
            'region' => 'required|string',
            'bucket' => 'required|string',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'key' => $input['key'],
            'secret' => $input['secret'],
            'region' => $input['region'],
            'bucket' => $input['bucket'],
        ];
    }

    public function connect(): bool
    {
        try {
            $this->getClient()->listBuckets();
            return true;
        } catch (S3Exception $e) {
            Log::error('Failed to connect to Wasabi S3', ['exception' => $e]);
            return false;
        }
    }

    public function ssh(Server $server): Storage
    {
        return new WasabiStorage($server, $this->storageProvider);
    }

    public function delete(array $paths): void
    {
        $client = $this->getClient();
        $bucket = $this->storageProvider->credentials['bucket'];

        foreach ($paths as $path) {
            try {
                $client->deleteObject([
                    'Bucket' => $bucket,
                    'Key' => $path,
                ]);
            } catch (S3Exception $e) {
                Log::error("Failed to delete object: $path", ['exception' => $e]);
            }
        }
    }

    private function getClient(): S3Client
    {
        if (!$this->client) {
            $credentials = $this->storageProvider->credentials;
            $this->client = new S3Client([
                'credentials' => [
                    'key' => $credentials['key'],
                    'secret' => $credentials['secret'],
                ],
                'region' => self::DEFAULT_REGION,
                'version' => 'latest',
                'endpoint' => self::API_URL,
                'use_path_style_endpoint' => true,
            ]);
        }
        return $this->client;
    }
}
