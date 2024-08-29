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
            \App\Facades\FTP::close($connection);
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
                \App\Facades\FTP::passive($connection, true);
            }

            foreach ($paths as $path) {
                \App\Facades\FTP::delete($connection, $this->storageProvider->credentials['path'].'/'.$path);
            }
        }

        \App\Facades\FTP::close($connection);
    }

    private function connection(): bool|Connection
    {
        $credentials = $this->storageProvider->credentials;

        return \App\Facades\FTP::connect(
            $credentials['host'],
            $credentials['port'],
            $credentials['ssl']
        );
    }

    private function login(bool|Connection $connection): bool
    {
        $credentials = $this->storageProvider->credentials;

        return \App\Facades\FTP::login(
            $credentials['username'],
            $credentials['password'],
            $connection
        );
    }
}
