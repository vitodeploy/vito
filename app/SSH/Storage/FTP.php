<?php

namespace App\SSH\Storage;

use App\SSH\HasScripts;

class FTP extends AbstractStorage
{
    use HasScripts;

    public function upload(string $src, string $dest): array
    {
        $this->server->ssh()->exec(
            $this->getScript('ftp/upload.sh', [
                'src' => $src,
                'dest' => $dest,
                'host' => $this->storageProvider->credentials['host'],
                'port' => $this->storageProvider->credentials['port'],
                'username' => $this->storageProvider->credentials['username'],
                'password' => $this->storageProvider->credentials['password'],
                'ssl' => $this->storageProvider->credentials['ssl'],
                'passive' => $this->storageProvider->credentials['passive'],
            ]),
            'upload-to-ftp'
        );

        return [
            'size' => null,
        ];
    }

    public function download(string $src, string $dest): void
    {
        $this->server->ssh()->exec(
            $this->getScript('ftp/download.sh', [
                'src' => $src,
                'dest' => $dest,
                'host' => $this->storageProvider->credentials['host'],
                'port' => $this->storageProvider->credentials['port'],
                'username' => $this->storageProvider->credentials['username'],
                'password' => $this->storageProvider->credentials['password'],
                'ssl' => $this->storageProvider->credentials['ssl'],
                'passive' => $this->storageProvider->credentials['passive'],
            ]),
            'download-from-ftp'
        );
    }

    public function delete(string $src): void
    {
        $this->server->ssh()->exec(
            $this->getScript('ftp/delete-file.sh', [
                'src' => $src,
                'host' => $this->storageProvider->credentials['host'],
                'port' => $this->storageProvider->credentials['port'],
                'username' => $this->storageProvider->credentials['username'],
                'password' => $this->storageProvider->credentials['password'],
                'ssl' => $this->storageProvider->credentials['ssl'],
                'passive' => $this->storageProvider->credentials['passive'],
            ]),
            'delete-from-ftp'
        );
    }
}
