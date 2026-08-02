<?php

namespace App\StorageProviders;

use App\DTOs\DynamicField;
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

    public static function editFields(): array
    {
        return [
            DynamicField::make('host')
                ->text()
                ->label('Host'),
            DynamicField::make('port')
                ->text()
                ->label('Port'),
            DynamicField::make('path')
                ->text()
                ->label('Path'),
            DynamicField::make('username')
                ->text()
                ->label('Username'),
            DynamicField::make('password')
                ->passwordWithToggle()
                ->label('Password')
                ->description('Leave empty to keep the current password'),
            DynamicField::make('ssl')
                ->checkbox()
                ->label('Use SSL'),
            DynamicField::make('passive')
                ->checkbox()
                ->label('Use Passive Mode'),
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

    public function connect(array $credentials): bool
    {
        $connection = $this->connection($credentials);

        $isConnected = $connection && $this->login($connection, $credentials);

        if ($connection) {
            \App\Facades\FTP::close($connection);
        }

        return $isConnected;
    }

    public function ssh(Server $server): Storage
    {
        return new \App\SSH\Storage\FTP($server, $this->storageProvider);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function connection(array $credentials): bool|Connection
    {
        return \App\Facades\FTP::connect(
            $credentials['host'],
            (int) $credentials['port'],
            (bool) $credentials['ssl']
        );
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function login(bool|Connection $connection, array $credentials): bool
    {
        return \App\Facades\FTP::login(
            $credentials['username'],
            $credentials['password'],
            $connection
        );
    }
}
