<?php

namespace App\StorageProviders;

use App\DTOs\DynamicField;
use App\Models\Server;
use App\SSH\Storage\Storage;

class Local extends AbstractStorageProvider
{
    public static function id(): string
    {
        return 'local';
    }

    public function validationRules(): array
    {
        return [
            'path' => 'required',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'path' => $input['path'],
        ];
    }

    public static function editFields(): array
    {
        return [
            DynamicField::make('path')
                ->text()
                ->label('Path'),
        ];
    }

    protected function editableFields(): array
    {
        return ['path'];
    }

    public function connect(array $credentials): bool
    {
        return true;
    }

    public function ssh(Server $server): Storage
    {
        return new \App\SSH\Storage\Local($server, $this->storageProvider);
    }
}
