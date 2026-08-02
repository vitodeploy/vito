<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSH\Storage\Storage;
use FTP\Connection;

class FTP extends AbstractStorageProvider
{
    public static function id(): string
    {
        return 'ftp';
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRules(): array
    {
        return [
            'host' => 'required',
            'port' => [
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],
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

    protected function editableFields(): array
    {
        return ['host', 'port', 'path', 'username', 'ssl', 'passive'];
    }

    protected function secretFields(): array
    {
        return ['password'];
    }

    protected function editFieldRules(): array
    {
        return [
            'port' => ['integer', 'min:1', 'max:65535'],
            'ssl' => ['boolean'],
            'passive' => ['boolean'],
        ];
    }

    public function mergeEditData(array $input): array
    {
        [$credentials, $needsReconnect] = parent::mergeEditData($input);

        $credentials['ssl'] = (bool) ($credentials['ssl'] ?? false);
        $credentials['passive'] = (bool) ($credentials['passive'] ?? true);

        return [$credentials, $needsReconnect];
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

    private function connection(): bool|Connection
    {
        $credentials = $this->storageProvider->credentials;

        return \App\Facades\FTP::connect(
            $credentials['host'],
            (int) $credentials['port'],
            (bool) $credentials['ssl']
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
