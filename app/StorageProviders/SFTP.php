<?php

namespace App\StorageProviders;

use App\DTOs\DynamicField;
use App\Facades\SFTP as SFTPFacade;
use App\Models\Server;
use App\SSH\Storage\Storage;

class SFTP extends AbstractStorageProvider
{
    public static function id(): string
    {
        return 'sftp';
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
        ];
    }

    protected function editableFields(): array
    {
        return ['host', 'port', 'path', 'username'];
    }

    protected function secretFields(): array
    {
        return ['password'];
    }

    public function connect(array $credentials): bool
    {
        return SFTPFacade::connect(
            $credentials['host'],
            (int) $credentials['port'],
            $credentials['username'],
            $credentials['password']
        );
    }

    public function ssh(Server $server): Storage
    {
        return new \App\SSH\Storage\SFTP($server, $this->storageProvider);
    }
}
