<?php

namespace App\SSH\Storage;

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use Illuminate\Support\Facades\Log;

class Dropbox extends AbstractStorage
{
    /**
     * @throws SSHError
     */
    public function upload(string $src, string $dest): array
    {
        $upload = $this->server->ssh()->exec(
            view('ssh.storage.dropbox.upload', [
                'src' => $src,
                'dest' => $dest,
                'token' => $this->storageProvider->credentials['token'],
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
                'token' => $this->storageProvider->credentials['token'],
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
                'token' => $this->storageProvider->credentials['token'],
            ]),
            'delete-from-dropbox'
        );
    }
}
