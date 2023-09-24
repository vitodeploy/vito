<?php

namespace App\StorageProviders;

use App\Exceptions\BackupFileException;
use App\Models\Server;
use App\SSHCommands\Storage\DownloadFromDropboxCommand;
use App\SSHCommands\Storage\UploadToDropboxCommand;
use Illuminate\Support\Facades\Http;
use Throwable;

class Dropbox extends AbstractStorageProvider
{
    protected string $apiUrl = 'https://api.dropboxapi.com/2';

    public function validationRules(): array
    {
        return [
            'token' => 'required'
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'token' => $input['token']
        ];
    }

    public function connect(): bool
    {
        $res = Http::withToken($this->storageProvider->credentials['token'])
            ->post($this->apiUrl.'/check/user', [
                'query' => '',
            ]);

        return $res->successful();
    }

    /**
     * @throws Throwable
     */
    public function upload(Server $server, string $src, string $dest): array
    {
        $upload = $server->ssh()->exec(
            new UploadToDropboxCommand(
                $src,
                $dest,
                $this->storageProvider->credentials['token']
            ),
            'upload-to-dropbox'
        );

        $data = json_decode($upload, true);

        if (isset($data['error'])) {
            throw new BackupFileException('Failed to upload to Dropbox '.$data['error_summary'] ?? '');
        }

        return [
            'size' => $data['size'] ?? null,
        ];
    }

    /**
     * @throws Throwable
     */
    public function download(Server $server, string $src, string $dest): void
    {
        $server->ssh()->exec(
            new DownloadFromDropboxCommand(
                $src,
                $dest,
                $this->storageProvider->credentials['token']
            ),
            'download-from-dropbox'
        );
    }

    public function delete(array $paths): void
    {
        $data = [];
        foreach ($paths as $path) {
            $data[] = ['path' => $path];
        }
        Http::withToken($this->storageProvider->credentials['token'])
            ->withHeaders([
                'Content-Type:application/json',
            ])
            ->post($this->apiUrl.'/files/delete_batch', [
                'entries' => $data,
            ]);
    }
}
