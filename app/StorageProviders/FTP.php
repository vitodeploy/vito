<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSH\Storage\Storage;
use FTP\Connection;

class FTP extends AbstractStorageProvider
{
    public function validationRules(): array
    {
        return [
            'host' => 'required',
            'port' => 'required|numeric',
            'path' => 'required',
            'username' => 'required',
            'password' => 'required',
            'ssl' => 'required',
            'passive' => 'required',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'host' => $input['host'],
            'port' => $input['port'],
            'path' => $input['path'],
            'username' => $input['username'],
            'password' => $input['password'],
            'ssl' => (bool) $input['ssl'],
            'passive' => (bool) $input['passive'],
        ];
    }

    public function connect(): bool
    {
        $connection = $this->connection();

        $isConnected = $connection && $this->login($connection);

        if ($isConnected) {
            ftp_close($connection);
        }

        return $isConnected;
    }

    public function ssh(Server $server): Storage
    {
        return new \App\SSH\Storage\FTP($server, $this->storageProvider);
    }

    public function delete(array $paths): void
    {
        $connection = $this->connection();

        if ($connection && $this->login($connection)) {
            if ($this->storageProvider->credentials['passive']) {
                ftp_pasv($connection, true);
            }

            foreach ($paths as $path) {
                ftp_delete($connection, $this->storageProvider->credentials['path'].'/'.$path);
            }
        }

        ftp_close($connection);
    }

    private function connection(): bool|Connection
    {
        $credentials = $this->storageProvider->credentials;
        if ($credentials['ssl']) {
            return ftp_ssl_connect($credentials['host'], $credentials['port'], 5);
        }

        return ftp_connect($credentials['host'], $credentials['port'], 5);
    }

    private function login(Connection $connection): bool
    {
        $credentials = $this->storageProvider->credentials;

        return ftp_login($connection, $credentials['username'], $credentials['password']);
    }
}
