<?php

namespace App\SSH\Storage;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\StorageProviders\Dropbox as DropboxProvider;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class Dropbox extends AbstractStorage
{
    private function accessToken(): string
    {
        $provider = $this->storageProvider->provider();

        if (! $provider instanceof DropboxProvider) {
            throw new RuntimeException('Storage provider is not Dropbox.');
        }

        return $provider->accessToken();
    }

    /**
     * @throws SSHError
     */
    public function upload(string $src, string $dest): array
    {
        $upload = $this->server->ssh()->exec(
            view('ssh.storage.dropbox.upload', [
                'src' => $src,
                'dest' => $dest,
                'token' => $this->accessToken(),
            ]),
            'upload-to-dropbox'
        );

        $data = json_decode($upload, true);

        if (isset($data['error'])) {
            Log::error('Failed to upload to Dropbox', $data);
            throw new SSHCommandError('Failed to upload to Dropbox');
        }

        return [
            'size' => $data['size'] ?? null,
        ];
    }

    /**
     * @throws SSHError
     */
    public function download(string $src, string $dest): void
    {
        $this->server->ssh()->exec(
            view('ssh.storage.dropbox.download', [
                'src' => $src,
                'dest' => $dest,
                'token' => $this->accessToken(),
            ]),
            'download-from-dropbox'
        );
    }

    /**
     * @throws SSHError
     */
    public function delete(string $src): void
    {
        $this->server->ssh()->exec(
            view('ssh.storage.dropbox.delete-file', [
                'src' => $src,
                'token' => $this->accessToken(),
            ]),
            'delete-from-dropbox'
        );
    }
}
