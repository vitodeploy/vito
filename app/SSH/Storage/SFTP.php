<?php

namespace App\SSH\Storage;

use App\Exceptions\SSHError;

class SFTP extends AbstractStorage
{
    /**
     * @throws SSHError
     */
    public function upload(string $src, string $dest): array
    {
        $this->server->ssh()->exec(
            view('ssh.storage.sftp.upload', [
                'src' => $src,
                'dest' => $dest,
                'host' => $this->storageProvider->credentials['host'],
                'port' => $this->storageProvider->credentials['port'],
                'username' => $this->storageProvider->credentials['username'],
                'password' => $this->storageProvider->credentials['password'],
            ]),
            'upload-to-sftp'
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
            view('ssh.storage.sftp.download', [
                'src' => $src,
                'dest' => $dest,
                'host' => $this->storageProvider->credentials['host'],
                'port' => $this->storageProvider->credentials['port'],
                'username' => $this->storageProvider->credentials['username'],
                'password' => $this->storageProvider->credentials['password'],
            ]),
            'download-from-sftp'
        );
    }

    /**
     * @throws SSHError
     */
    public function delete(string $src): void
    {
        $this->server->ssh()->exec(
            view('ssh.storage.sftp.delete-file', [
                'src' => $src,
                'host' => $this->storageProvider->credentials['host'],
                'port' => $this->storageProvider->credentials['port'],
                'username' => $this->storageProvider->credentials['username'],
                'password' => $this->storageProvider->credentials['password'],
            ]),
            'delete-from-sftp'
        );
    }
}
