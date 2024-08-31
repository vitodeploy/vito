<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSH\Storage\S3 as S3Storage;
use App\SSH\Storage\Storage;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Log;

class S3 extends S3AbstractStorageProvider
{
    public function validationRules(): array
    {
        return [
            'key' => 'required|string',
            'secret' => 'required|string',
            'region' => 'required|string',
            'bucket' => 'required|string',
            'path' => 'required|string',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'key' => $input['key'],
            'secret' => $input['secret'],
            'region' => $input['region'],
            'bucket' => $input['bucket'],
            'path' => $input['path'],
        ];
    }

    public function connect(): bool
    {
        try {
            $this->setBucketRegion($this->storageProvider->credentials['region']);
            $this->setApiUrl();
            $this->buildClientConfig();
            $this->getClient()->listBuckets();

            return true;
        } catch (S3Exception $e) {
            Log::error('Failed to connect to S3', ['exception' => $e]);

            return false;
        }
    }

    public function ssh(Server $server): Storage
    {
        return new S3Storage($server, $this->storageProvider);
    }

    public function delete(array $paths): void {}
}
