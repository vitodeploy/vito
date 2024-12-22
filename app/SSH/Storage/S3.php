<?php

namespace App\SSH\Storage;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\SSH\HasS3Storage;
use App\SSH\HasScripts;
use Illuminate\Support\Facades\Log;

class S3 extends AbstractStorage
{
    use HasS3Storage, HasScripts;

    /**
     * @throws SSHError
     */
    public function upload(string $src, string $dest): array
    {
        /** @var \App\StorageProviders\S3 $provider */
        $provider = $this->storageProvider->provider();

        $uploadCommand = $this->getScript('s3/upload.sh', [
            'src' => $src,
            'bucket' => $this->storageProvider->credentials['bucket'],
            'dest' => $this->prepareS3Path($this->storageProvider->credentials['path'].'/'.$dest),
            'key' => $this->storageProvider->credentials['key'],
            'secret' => $this->storageProvider->credentials['secret'],
            'region' => $this->storageProvider->credentials['region'],
            'endpoint' => $provider->getApiUrl(),
        ]);

        $upload = $this->server->ssh()->exec($uploadCommand, 'upload-to-s3');

        if (str_contains($upload, 'Error') || ! str_contains($upload, 'upload:')) {
            Log::error('Failed to upload to S3', ['output' => $upload]);
            throw new SSHCommandError('Failed to upload to S3: '.$upload);
        }

        return [
            'size' => null, // You can parse the size from the output if needed
        ];

    }

    /**
     * @throws SSHError
     */
    public function download(string $src, string $dest): void
    {
        /** @var \App\StorageProviders\S3 $provider */
        $provider = $this->storageProvider->provider();

        $downloadCommand = $this->getScript('s3/download.sh', [
            'src' => $this->prepareS3Path($this->storageProvider->credentials['path'].'/'.$src),
            'dest' => $dest,
            'bucket' => $this->storageProvider->credentials['bucket'],
            'key' => $this->storageProvider->credentials['key'],
            'secret' => $this->storageProvider->credentials['secret'],
            'region' => $this->storageProvider->credentials['region'],
            'endpoint' => $provider->getApiUrl(),
        ]);

        Log::info('Downloading from S3', ['command' => $downloadCommand]);

        $download = $this->server->ssh()->exec($downloadCommand, 'download-from-s3');

        if (! str_contains($download, 'Download successful')) {
            Log::error('Failed to download from S3', ['output' => $download]);
            throw new SSHCommandError('Failed to download from S3: '.$download);
        }
    }

    /**
     * @TODO Implement delete method
     */
    public function delete(string $path): void {}
}
