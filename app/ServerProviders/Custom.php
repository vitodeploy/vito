<?php

namespace App\ServerProviders;

use App\Exceptions\ServerProviderError;
use App\ValidationRules\RestrictedIPAddressesRule;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class Custom extends AbstractProvider
{
    public static function id(): string
    {
        return 'custom';
    }

    public function createRules(array $input): array
    {
        return [
            'ip' => [
                'required',
                Rule::unique('servers', 'ip'),
                new RestrictedIPAddressesRule,
            ],
            'port' => [
                'required',
                'numeric',
                'min:1',
                'max:65535',
            ],
        ];
    }

    public function credentialValidationRules(array $input): array
    {
        return [];
    }

    public function credentialData(array $input): array
    {
        return [];
    }

    public function data(array $input): array
    {
        return [];
    }

    public function connect(array $credentials): bool
    {
        return true;
    }

    public function plans(?string $region): array
    {
        return [];
    }

    public function regions(): array
    {
        return [];
    }

    public function create(): void
    {
        /** @var FilesystemAdapter $storageDisk */
        $storageDisk = Storage::disk(config('core.key_pairs_disk'));
        File::copy(
            storage_path(config('core.ssh_private_key_name')),
            $storageDisk->path((string) $this->server->id)
        );
        File::copy(
            storage_path(config('core.ssh_public_key_name')),
            $storageDisk->path($this->server->id.'.pub')
        );

        try {
            $this->server->ssh('root')->connect();
        } catch (Throwable) {
            throw new ServerProviderError('Cannot connect to server, make sure you have copied the public key.');
        }

        $output = $this->server->ssh('root')->exec('id -u vito 2>/dev/null || echo "user_not_found"');
        if (! str_contains($output, 'user_not_found')) {
            throw new ServerProviderError('You cannot perform this action on Vito\'s server itself.');
        }
    }

    public function isRunning(): bool
    {
        return true;
    }

    public function delete(): void
    {
        //
    }
}
