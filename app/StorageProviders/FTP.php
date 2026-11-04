<?php

namespace App\StorageProviders;

use App\DTOs\DynamicField;
use App\Facades\FTP as FTPFacade;
use App\Models\BackupFile;
use App\Models\Server;
use App\SSH\Storage\Storage;
use FTP\Connection;
use GuzzleHttp\Psr7\UriResolver;
use RuntimeException;
use Throwable;

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

    public function download(BackupFile $backupFile): array
    {
        $credentials = $this->storageProvider->credentials;
        $connection = $this->connection($credentials);
        if (! $connection) {
            throw new RuntimeException('Unable to connect to FTP storage.');
        }

        try {
            if (! $this->login($connection, $credentials) || ! FTPFacade::passive($connection, (bool) $credentials['passive'])) {
                throw new RuntimeException('Unable to authenticate or configure FTP storage.');
            }
            $path = rawurldecode(substr(UriResolver::removeDotSegments('/'.$backupFile->path()), 1));
            $size = FTPFacade::size($connection, $path);
            abort_if($size < 0, 404, 'The backup file does not exist or its size is unavailable.');
        } catch (Throwable $e) {
            FTPFacade::close($connection);
            throw $e;
        }

        return [
            'size' => $size,
            'stream' => static function () use ($connection, $path): void {
                try {
                    FTPFacade::streamDownload($connection, $path);
                } finally {
                    FTPFacade::close($connection);
                }
            },
        ];
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
