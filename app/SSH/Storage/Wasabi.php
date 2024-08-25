<?php

namespace App\SSH\Storage;

use App\Exceptions\SSHCommandError;
use App\SSH\HasScripts;
use Illuminate\Support\Facades\Log;

class Wasabi extends AbstractStorage
{
    use HasScripts;

    /**
     * @throws SSHCommandError
     */
    public function upload(string $src, string $dest): array
    {
        $uploadCommand = $this->getScript('wasabi/upload.sh', [
            'src' => $src,
            'bucket' => $this->storageProvider->credentials['bucket'],
            'dest' => $dest,
            'key' => $this->storageProvider->credentials['key'],
            'secret' => $this->storageProvider->credentials['secret'],
            'region' => $this->storageProvider->credentials['region']
        ]);

        $upload = $this->server->ssh()->exec($uploadCommand, 'upload-to-s3');

        if (str_contains($upload, 'Error') || !str_contains($upload, 'upload:')) {
            Log::error('Failed to upload to S3', ['output' => $upload]);
            throw new SSHCommandError('Failed to upload to S3: ' . $upload);
        }

        return [
            'size' => null, // You can parse the size from the output if needed
        ];

    }

    /**
     * @throws SSHCommandError
     */
    public function download(string $src, string $dest): void
    {
        $downloadCommand = $this->getScript('wasabi/download.sh', [
            'src' => $src,
            'dest' => $dest,
            'bucket' => $this->storageProvider->credentials['bucket'],
            'key' => $this->storageProvider->credentials['key'],
            'secret' => $this->storageProvider->credentials['secret'],
            'region' => $this->storageProvider->credentials['region']
        ]);

        $download = $this->server->ssh()->exec($downloadCommand, 'download-from-s3');

        if (!str_contains($download, 'Download successful')) {
            Log::error('Failed to download from S3', ['output' => $download]);
            throw new SSHCommandError('Failed to download from S3: ' . $download);
        }
    }

    /**
     * @TODO Implement delete method
     */
    public function delete(string $path): void
    {

    }
}
