<?php

namespace App\SSH\Storage;

use App\Exceptions\SSHError;

class FTP extends AbstractStorage
{
    /**
     * @throws SSHError
     */
    public function upload(string $src, string $dest): array
    {
        $this->server->ssh()->exec(
            view('ssh.storage.ftp.upload', [
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

    /**
     * @throws SSHError
     */
    public function download(string $src, string $dest): void
    {
        $this->server->ssh()->exec(
            view('ssh.storage.ftp.download', [
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

    /**
     * @throws SSHError
     */
    public function delete(string $src): void
    {
        $this->server->ssh()->exec(
            view('ssh.storage.ftp.delete-file', [
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
