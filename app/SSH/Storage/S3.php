<?php

namespace App\SSH\Storage;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\SSH\HasS3Storage;
use Illuminate\Support\Facades\Log;

class S3 extends AbstractStorage
{
    use HasS3Storage;

    /**
     * @throws SSHError
     */
    public function upload(string $src, string $dest): array
    {
        /** @var \App\StorageProviders\S3 $provider */
        $provider = $this->storageProvider->provider();

        $uploadCommand = view('ssh.storage.s3.upload', [
            'src' => $src,
            'bucket' => $this->storageProvider->credentials['bucket'],
            'dest' => $this->prepareS3Path($dest),
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

        $downloadCommand = view('ssh.storage.s3.download', [
            'src' => $this->prepareS3Path($src),
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
     * @throws SSHError
     */
    public function delete(string $src): void
    {
        /** @var \App\StorageProviders\S3 $provider */
        $provider = $this->storageProvider->provider();

        $this->server->ssh()->exec(
            view('ssh.storage.s3.delete-file', [
                'src' => $this->prepareS3Path($src),
                'bucket' => $this->storageProvider->credentials['bucket'],
                'key' => $this->storageProvider->credentials['key'],
                'secret' => $this->storageProvider->credentials['secret'],
                'region' => $this->storageProvider->credentials['region'],
                'endpoint' => $provider->getApiUrl(),
            ]),
            'delete-from-s3'
        );
    }
}
