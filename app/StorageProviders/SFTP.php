<?php

namespace App\StorageProviders;

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

    public function connect(): bool
    {
        $credentials = $this->storageProvider->credentials;

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
