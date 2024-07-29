<?php

namespace App\SSH\Storage;

use App\Exceptions\SSHCommandError;
use App\SSH\HasScripts;
use Illuminate\Support\Facades\Log;

class Dropbox extends AbstractStorage
{
    use HasScripts;

    public function upload(string $src, string $dest): array
    {
        $upload = $this->server->ssh()->exec(
            $this->getScript('dropbox/upload.sh', [
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

    public function download(string $src, string $dest): void
    {
        $this->server->ssh()->exec(
            $this->getScript('dropbox/download.sh', [
                'src' => $src,
                'dest' => $dest,
                'token' => $this->storageProvider->credentials['token'],
            ]),
            'download-from-dropbox'
        );
    }

    /**
     * @TODO Implement delete method
     */
    public function delete(string $path): void
    {
        //
    }
}
