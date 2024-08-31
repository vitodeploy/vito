<?php

namespace App\SSH\Storage;

use App\Exceptions\SSHCommandError;
use App\Models\Server;
use App\Models\StorageProvider;
use App\SSH\HasS3Storage;
use App\SSH\HasScripts;
use Illuminate\Support\Facades\Log;

class Wasabi extends S3AbstractStorage
{
    use HasS3Storage, HasScripts;

    public function __construct(Server $server, StorageProvider $storageProvider)
    {
        parent::__construct($server, $storageProvider);
        $this->setBucketRegion($this->storageProvider->credentials['region']);
        $this->setApiUrl();
    }

    /**
     * @throws SSHCommandError
     */
    public function upload(string $src, string $dest): array
    {
        $uploadCommand = $this->getScript('wasabi/upload.sh', [
            'src' => $src,
            'bucket' => $this->storageProvider->credentials['bucket'],
            'dest' => $this->prepareS3Path($this->storageProvider->credentials['path'].'/'.$dest),
            'key' => $this->storageProvider->credentials['key'],
            'secret' => $this->storageProvider->credentials['secret'],
            'region' => $this->storageProvider->credentials['region'],
            'endpoint' => $this->getApiUrl(),
        ]);

        $upload = $this->server->ssh()->exec($uploadCommand, 'upload-to-wasabi');

        if (str_contains($upload, 'Error') || ! str_contains($upload, 'upload:')) {
            Log::error('Failed to upload to wasabi', ['output' => $upload]);
            throw new SSHCommandError('Failed to upload to wasabi: '.$upload);
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
            'src' => $this->prepareS3Path($this->storageProvider->credentials['path'].'/'.$src),
            'dest' => $dest,
            'bucket' => $this->storageProvider->credentials['bucket'],
            'key' => $this->storageProvider->credentials['key'],
            'secret' => $this->storageProvider->credentials['secret'],
            'region' => $this->storageProvider->credentials['region'],
            'endpoint' => $this->getApiUrl(),
        ]);

        $download = $this->server->ssh()->exec($downloadCommand, 'download-from-wasabi');

        if (! str_contains($download, 'Download successful')) {
            Log::error('Failed to download from wasabi', ['output' => $download]);
            throw new SSHCommandError('Failed to download from wasabi: '.$download);
        }
    }

    /**
     * @TODO Implement delete method
     */
    public function delete(string $path): void {}

    public function setApiUrl(?string $region = null): void
    {
        $this->bucketRegion = $region ?? $this->bucketRegion;
        $this->apiUrl = "https://{$this->storageProvider->credentials['bucket']}.s3.{$this->getBucketRegion()}.wasabisys.com";
    }
}
